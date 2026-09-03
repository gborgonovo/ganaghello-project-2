<?php

namespace Tests\Feature;

use App\Jobs\SendDailyDigest;
use App\Mail\DailyDigest;
use App\Models\DigestReminder;
use App\Models\Setting;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use App\Services\MnemosyneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Le soglie del digest sono promemoria a giorno esatto, non fasce: la mail parte
 * solo quando un task raggiunge D-30/-15/-7/-3/-1/0 o, da scaduto, D+1/+3/+7/+15/+30
 * e poi ogni 30 giorni. Un giorno saltato dal worker viene recuperato al primo run
 * utile, ma senza doppioni (tabella digest_reminders). I dormienti non fanno mai
 * partire una mail da soli.
 */
class DigestRemindersTest extends TestCase
{
    use RefreshDatabase;

    private Stage $stage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stage = Stage::create(['code' => 'in_corso', 'label' => 'In corso']);
    }

    private function task(User $user, \DateTimeInterface $due): Task
    {
        return Task::create([
            'user_id'  => $user->id,
            'title'    => 'Task',
            'stage_id' => $this->stage->id,
            'due_date' => $due,
        ]);
    }

    private function seedSent(User $user, Task $task, array $offsets): void
    {
        foreach ($offsets as $offset) {
            DigestReminder::create([
                'user_id' => $user->id,
                'task_id' => $task->id,
                'offset'  => $offset,
                'sent_on' => now()->subDay()->toDateString(),
            ]);
        }
    }

    private function runDigest(?MnemosyneService $mnemosyne = null): void
    {
        if ($mnemosyne === null) {
            $mnemosyne = $this->mock(MnemosyneService::class);
            $mnemosyne->shouldReceive('enabled')->andReturn(false);
        }
        (new SendDailyDigest())->handle($mnemosyne);
    }

    public function test_reminder_fires_on_exact_threshold_day(): void
    {
        $mail = Mail::fake();
        $user = User::factory()->create();
        $task = $this->task($user, today()->addDays(7));
        $this->seedSent($user, $task, [-30, -15]); // le soglie precedenti sono gia' passate

        $this->runDigest();

        $mail->assertSent(DailyDigest::class, fn ($m) => $m->hasTo($user->email));
        $this->assertDatabaseHas('digest_reminders', ['task_id' => $task->id, 'offset' => -7]);
    }

    public function test_no_reminder_between_thresholds(): void
    {
        $mail = Mail::fake();
        $user = User::factory()->create();
        $task = $this->task($user, today()->addDays(20)); // bucket -30, gia' inviato
        $this->seedSent($user, $task, [-30]);

        $this->runDigest();

        $mail->assertNothingSent();
    }

    public function test_dedup_prevents_resend_same_day(): void
    {
        $mail = Mail::fake();
        $user = User::factory()->create();
        $this->task($user, today()->addDays(3));

        $this->runDigest();
        $this->runDigest();

        $mail->assertSent(DailyDigest::class, 1);
    }

    public function test_missed_threshold_day_is_caught_up(): void
    {
        $mail = Mail::fake();
        $user = User::factory()->create();
        // Worker fermo nel giorno esatto (D-7): oggi mancano 6 giorni, ancora dentro il bucket -7.
        $task = $this->task($user, today()->addDays(6));
        $this->seedSent($user, $task, [-30, -15]);

        $this->runDigest();

        $mail->assertSent(DailyDigest::class, function ($m) {
            return collect($m->groups)->contains(fn ($g) => $g['label'] === 'Tra 6 giorni' && $g['late'] === false);
        });
        $this->assertDatabaseHas('digest_reminders', ['task_id' => $task->id, 'offset' => -7]);
    }

    public function test_same_day_reminder_respects_zero_toggle(): void
    {
        $mail = Mail::fake();
        Setting::set('digest.thresholds', json_encode([1, 3, 7, 15, 30])); // niente "giorno stesso"
        $user = User::factory()->create();
        $this->task($user, today());

        $this->runDigest();

        $mail->assertNothingSent();
    }

    public function test_overdue_reminder_fires_on_plus_offset(): void
    {
        $mail = Mail::fake();
        $user = User::factory()->create();
        $task = $this->task($user, today()->subDays(3));
        $this->seedSent($user, $task, [1]); // il +1 e' gia' partito

        $this->runDigest();

        $mail->assertSent(DailyDigest::class, function ($m) {
            return collect($m->groups)->contains(fn ($g) => $g['label'] === 'Scaduto da 3 giorni' && $g['late'] === true);
        });
        $this->assertDatabaseHas('digest_reminders', ['task_id' => $task->id, 'offset' => 3]);
    }

    public function test_no_overdue_reminder_off_threshold(): void
    {
        $mail = Mail::fake();
        $user = User::factory()->create();
        $task = $this->task($user, today()->subDays(5)); // bucket +3, gia' inviato
        $this->seedSent($user, $task, [1, 3]);

        $this->runDigest();

        $mail->assertNothingSent();
    }

    public function test_monthly_tail_after_thirty_days(): void
    {
        $mail = Mail::fake();
        $user = User::factory()->create();
        $task = $this->task($user, today()->subDays(60));
        $this->seedSent($user, $task, [1, 3, 7, 15, 30]);

        $this->runDigest();

        $mail->assertSent(DailyDigest::class, fn ($m) => $m->hasTo($user->email));
        $this->assertDatabaseHas('digest_reminders', ['task_id' => $task->id, 'offset' => 60]);
    }

    public function test_dormant_nodes_do_not_send_without_task_reminder(): void
    {
        $mail = Mail::fake();
        $user = User::factory()->create();
        $task = $this->task($user, today()->subDays(5)); // scaduto ma fuori soglia (bucket +3 gia' inviato)
        $this->seedSent($user, $task, [1, 3]);

        $mnemosyne = $this->mock(MnemosyneService::class);
        $mnemosyne->shouldReceive('enabled')->andReturn(true);
        $mnemosyne->shouldReceive('briefing')->andReturn(['dormant' => [['name' => 'Nodo X', 'days_inactive' => 40]]]);

        $this->runDigest($mnemosyne);

        $mail->assertNothingSent();
    }

    public function test_dormant_nodes_ride_along_with_a_task_reminder(): void
    {
        $mail = Mail::fake();
        $user = User::factory()->create();
        $this->task($user, today()); // bucket 0 -> promemoria oggi

        $mnemosyne = $this->mock(MnemosyneService::class);
        $mnemosyne->shouldReceive('enabled')->andReturn(true);
        $mnemosyne->shouldReceive('briefing')->andReturn(['dormant' => [['name' => 'Nodo X', 'days_inactive' => 40]]]);
        $mnemosyne->shouldReceive('resolveNode')->with('Nodo X')->andReturn([
            'label'      => 'Nodo X',
            'url'        => 'https://example.test/x',
            'updated_at' => null,
        ]);

        $this->runDigest($mnemosyne);

        $mail->assertSent(DailyDigest::class, fn ($m) => count($m->dormant) === 1 && $m->dormant[0]['label'] === 'Nodo X');
    }
}
