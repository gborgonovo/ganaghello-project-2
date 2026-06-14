<?php

namespace Tests\Feature;

use App\Livewire\TaskDetail;
use App\Models\Expense;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Blocco 7.2: spese di dettaglio del task, inserite a mano (niente upload),
 * con categoria e fornitore; la somma fa da riferimento accanto a cost_real.
 */
class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    private Stage $stage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stage = Stage::create(['code' => 'todo', 'label' => 'To do', 'sequence' => 4]);
    }

    private function task(): Task
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        return Task::create(['user_id' => $user->id, 'title' => 'Rifacimento tetto', 'stage_id' => $this->stage->id, 'due_date' => today()]);
    }

    public function test_aggiunta_spesa_con_categoria_e_fornitore(): void
    {
        $task = $this->task();

        Livewire::test(TaskDetail::class, ['task' => $task])
            ->call('newExpense')
            ->set('expDescription', 'Acconto idraulico')
            ->set('expAmount', '2000')
            ->set('expCategory', 'manodopera')
            ->set('expSupplier', 'Rossi snc')
            ->call('saveExpense')
            ->assertDispatched('toast');

        $e = $task->expenses()->first();
        $this->assertNotNull($e);
        $this->assertSame('Acconto idraulico', $e->description);
        $this->assertSame(2000.0, $e->amount);
        $this->assertSame('manodopera', $e->category);
        $this->assertSame('Rossi snc', $e->supplier);
    }

    public function test_importo_obbligatorio(): void
    {
        $task = $this->task();

        Livewire::test(TaskDetail::class, ['task' => $task])
            ->call('newExpense')
            ->set('expDescription', 'Senza importo')
            ->set('expAmount', '')
            ->call('saveExpense')
            ->assertHasErrors('expAmount');

        $this->assertSame(0, $task->expenses()->count());
    }

    public function test_somma_spese_come_riferimento(): void
    {
        $task = $this->task();
        $task->expenses()->createMany([
            ['description' => 'A', 'amount' => 1500.50],
            ['description' => 'B', 'amount' => 3000.00],
        ]);

        $this->assertSame(4500.50, (float) $task->expenses()->sum('amount'));
    }

    public function test_modifica_spesa(): void
    {
        $task = $this->task();
        $e = $task->expenses()->create(['description' => 'Sanitari', 'amount' => 800]);

        Livewire::test(TaskDetail::class, ['task' => $task])
            ->call('editExpense', $e->id)
            ->set('expAmount', '950')
            ->call('saveExpense')
            ->assertDispatched('toast');

        $this->assertSame(950.0, $e->fresh()->amount);
    }

    public function test_eliminazione_spesa(): void
    {
        $task = $this->task();
        $e = $task->expenses()->create(['description' => 'Da rimuovere', 'amount' => 100]);

        Livewire::test(TaskDetail::class, ['task' => $task])
            ->call('deleteExpense', $e->id)
            ->assertDispatched('toast');

        $this->assertDatabaseMissing('expenses', ['id' => $e->id]);
    }
}
