<?php

namespace Tests\Feature;

use App\Jobs\SendDailyDigest;
use App\Livewire\TaskDetail;
use App\Mail\DailyDigest;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use App\Services\MnemosyneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Blocco 6 (assegnazione e team): si assegna un task e si scelgono i
 * collaboratori dalla UI; questo sblocca i destinatari del digest.
 */
class TaskAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private Stage $stage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stage = Stage::create(['code' => 'todo', 'label' => 'To do', 'sequence' => 4]);
    }

    public function test_assegnazione_responsabile_via_ui(): void
    {
        $owner    = User::factory()->create();
        $assignee = User::factory()->create();
        $this->actingAs($owner);

        $task = Task::create(['user_id' => $owner->id, 'title' => 'Tetto', 'stage_id' => $this->stage->id, 'due_date' => today()]);

        Livewire::test(TaskDetail::class, ['task' => $task])
            ->set('assigned_to', $assignee->id)
            ->call('saveAssignee')
            ->assertDispatched('toast');

        $this->assertSame($assignee->id, $task->fresh()->assigned_to);
    }

    public function test_collaboratori_toggle_via_ui(): void
    {
        $owner  = User::factory()->create();
        $collab = User::factory()->create();
        $this->actingAs($owner);

        $task = Task::create(['user_id' => $owner->id, 'title' => 'Impianto', 'stage_id' => $this->stage->id, 'due_date' => today()]);

        $component = Livewire::test(TaskDetail::class, ['task' => $task])
            ->call('toggleCollaborator', $collab->id)
            ->assertDispatched('toast');

        $this->assertTrue($task->collaborators()->where('users.id', $collab->id)->exists());

        // Secondo toggle: rimuove
        $component->call('toggleCollaborator', $collab->id);
        $this->assertFalse($task->collaborators()->where('users.id', $collab->id)->exists());
    }

    public function test_assegnatario_riceve_il_digest(): void
    {
        $mail     = Mail::fake();
        $owner    = User::factory()->create();
        $assignee = User::factory()->create();
        $this->actingAs($owner);

        $task = Task::create(['user_id' => $owner->id, 'title' => 'Geometra', 'stage_id' => $this->stage->id, 'due_date' => today()]);

        // Assegna tramite la UI
        Livewire::test(TaskDetail::class, ['task' => $task])
            ->set('assigned_to', $assignee->id)
            ->call('saveAssignee');

        // Esegue il digest (Mnemosyne disattivato come nel pattern di DigestRecipientsTest)
        $mnemosyne = $this->mock(MnemosyneService::class);
        $mnemosyne->shouldReceive('enabled')->andReturn(false);
        (new SendDailyDigest())->handle($mnemosyne);

        $mail->assertSent(DailyDigest::class, fn ($m) => $m->hasTo($assignee->email));
    }
}
