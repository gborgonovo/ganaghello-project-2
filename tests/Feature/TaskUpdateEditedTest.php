<?php

namespace Tests\Feature;

use App\Livewire\TaskDetail;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Blocco 8: modificare un aggiornamento ne tocca updated_at (la vista usa lo
 * scarto > 1s per mostrare "(modificato)").
 */
class TaskUpdateEditedTest extends TestCase
{
    use RefreshDatabase;

    public function test_modifica_update_aggiorna_updated_at(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $stage = Stage::create(['code' => 'todo', 'label' => 'To do', 'sequence' => 4]);
        $task  = Task::create(['user_id' => $user->id, 'title' => 'T', 'stage_id' => $stage->id, 'due_date' => today()]);

        $update = $task->updates()->create(['content' => 'Originale']);
        $this->assertLessThanOrEqual(1, abs($update->updated_at->diffInSeconds($update->created_at)));

        // Modifica 2 secondi dopo
        $this->travel(2)->seconds();

        Livewire::test(TaskDetail::class, ['task' => $task])
            ->call('startEditUpdate', $update->id)
            ->set('editingUpdateContent', 'Modificato')
            ->call('saveUpdate')
            ->assertDispatched('toast');

        $update->refresh();
        $this->assertSame('Modificato', $update->content);
        $this->assertGreaterThan(1, abs($update->updated_at->diffInSeconds($update->created_at)));
    }
}
