<?php

namespace Tests\Feature;

use App\Jobs\SendDailyDigest;
use App\Mail\DailyDigest;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use App\Services\MnemosyneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DigestRecipientsTest extends TestCase
{
    use RefreshDatabase;

    private Stage $stageActive;
    private Stage $stageDone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stageActive = Stage::create(['code' => 'in_corso', 'label' => 'In corso']);
        $this->stageDone   = Stage::create(['code' => 'done',     'label' => 'Fatto']);
    }

    public function test_owner_receives_digest(): void
    {
        $mail = Mail::fake();
        $user = User::factory()->create();
        Task::create(['user_id' => $user->id, 'title' => 'Task owner', 'stage_id' => $this->stageActive->id, 'due_date' => today()]);

        $this->runDigest();

        $mail->assertSent(DailyDigest::class, fn($m) => $m->hasTo($user->email));
    }

    public function test_both_owner_and_assigned_user_receive_digest(): void
    {
        $mail     = Mail::fake();
        $owner    = User::factory()->create();
        $assigned = User::factory()->create();

        // Il task appare nel digest di entrambi: creator (user_id) e assegnatario (assigned_to)
        Task::create([
            'user_id'     => $owner->id,
            'assigned_to' => $assigned->id,
            'title'       => 'Task assegnato',
            'stage_id'    => $this->stageActive->id,
            'due_date'    => today(),
        ]);

        $this->runDigest();

        $mail->assertSent(DailyDigest::class, fn($m) => $m->hasTo($owner->email));
        $mail->assertSent(DailyDigest::class, fn($m) => $m->hasTo($assigned->email));
    }

    public function test_collaborator_receives_digest(): void
    {
        $mail         = Mail::fake();
        $owner        = User::factory()->create();
        $collaborator = User::factory()->create();

        $task = Task::create([
            'user_id'  => $owner->id,
            'title'    => 'Task collaborato',
            'stage_id' => $this->stageActive->id,
            'due_date' => today(),
        ]);
        $task->collaborators()->attach($collaborator->id);

        $this->runDigest();

        $mail->assertSent(DailyDigest::class, fn($m) => $m->hasTo($collaborator->email));
    }

    public function test_unrelated_user_does_not_receive_digest(): void
    {
        $mail      = Mail::fake();
        $owner     = User::factory()->create();
        $unrelated = User::factory()->create();

        Task::create(['user_id' => $owner->id, 'title' => 'Task di owner', 'stage_id' => $this->stageActive->id, 'due_date' => today()]);

        $this->runDigest();

        $mail->assertNotSent(DailyDigest::class, fn($m) => $m->hasTo($unrelated->email));
    }

    public function test_done_task_not_included(): void
    {
        $mail = Mail::fake();
        $user = User::factory()->create();
        Task::create(['user_id' => $user->id, 'title' => 'Task fatto', 'stage_id' => $this->stageDone->id, 'due_date' => today()]);

        $this->runDigest();

        $mail->assertNothingSent();
    }

    public function test_no_email_when_no_tasks(): void
    {
        $mail = Mail::fake();
        User::factory()->create();

        $this->runDigest();

        $mail->assertNothingSent();
    }

    private function runDigest(): void
    {
        $mnemosyne = $this->mock(MnemosyneService::class);
        $mnemosyne->shouldReceive('enabled')->andReturn(false);
        (new SendDailyDigest())->handle($mnemosyne);
    }
}
