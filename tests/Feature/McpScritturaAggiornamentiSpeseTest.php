<?php

namespace Tests\Feature;

use App\Livewire\TaskDetail;
use App\Mcp\Servers\BigLogServer;
use App\Mcp\Tools\CreaAggiornamento;
use App\Mcp\Tools\CreaSpesa;
use App\Mcp\Tools\Elimina;
use App\Mcp\Tools\ModificaAggiornamento;
use App\Mcp\Tools\ModificaSpesa;
use App\Mcp\Tools\Ripristina;
use App\Models\Expense;
use App\Models\RegistroScrittura;
use App\Models\Stage;
use App\Models\Task;
use App\Models\TaskUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

/** Parte B, passo 2: aggiornamenti dei task e spese, ed elimina/ripristina. */
class McpScritturaAggiornamentiSpeseTest extends TestCase
{
    use RefreshDatabase;

    private User $u;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.mnemosyne.sync' => false]);
        $this->u = User::factory()->create();
        $idea = Stage::create(['code' => 'idea', 'label' => 'Idea', 'sequence' => 1]);
        $this->task = Task::create(['user_id' => $this->u->id, 'title' => 'Tetto', 'stage_id' => $idea->id]);
    }

    private function server()
    {
        Sanctum::actingAs($this->u, ['mcp:leggi', 'mcp:scrivere']);

        return BigLogServer::actingAs($this->u);
    }

    public function test_aggiornamento_creato_corretto_eliminato_e_ripristinato(): void
    {
        $s = $this->server();
        $s->tool(CreaAggiornamento::class, ['task_id' => $this->task->id, 'testo' => 'Arrivate le tegole'])->assertOk()->assertSee('Arrivate le tegole');
        $u = TaskUpdate::first();

        $s->tool(ModificaAggiornamento::class, ['id' => $u->id, 'testo' => 'Arrivate le tegole rosse'])->assertOk();
        $this->assertSame('Arrivate le tegole rosse', $u->fresh()->content);

        $s->tool(Elimina::class, ['entita' => 'aggiornamento', 'id' => $u->id])->assertOk();
        $this->assertSoftDeleted('task_updates', ['id' => $u->id]);

        $s->tool(Ripristina::class, ['entita' => 'aggiornamento', 'id' => $u->id])->assertOk();
        $this->assertNotSoftDeleted('task_updates', ['id' => $u->id]);
    }

    public function test_aggiornamento_vuoto_e_un_errore(): void
    {
        $this->server()->tool(CreaAggiornamento::class, ['task_id' => $this->task->id, 'testo' => '   '])->assertHasErrors();
        $this->assertSame(0, TaskUpdate::count());
    }

    public function test_spesa_creata_e_modificata_parzialmente(): void
    {
        $s = $this->server();
        $s->tool(CreaSpesa::class, ['task_id' => $this->task->id, 'descrizione' => 'Tegole', 'importo' => 120.5, 'categoria' => 'materiali', 'data' => '2026-10-30'])
            ->assertOk()->assertSee(['"azione":"creata"', 'Tegole', '120.5']);
        $e = Expense::first();

        $s->tool(ModificaSpesa::class, ['id' => $e->id, 'fornitore' => 'Edilizia Rossi'])->assertOk();
        $e->refresh();
        $this->assertSame('Edilizia Rossi', $e->supplier);
        $this->assertSame(120.5, $e->amount);
        $this->assertSame('mcp', RegistroScrittura::where(['entita' => 'spesa', 'azione' => 'modifica'])->first()->porta);
    }

    public function test_spesa_con_importo_nullo_o_categoria_sconosciuta_e_un_errore(): void
    {
        $s = $this->server();
        $s->tool(CreaSpesa::class, ['task_id' => $this->task->id, 'descrizione' => 'X', 'importo' => 0])->assertHasErrors();
        $s->tool(CreaSpesa::class, ['task_id' => $this->task->id, 'descrizione' => 'X', 'importo' => 5, 'categoria' => 'cibo'])->assertHasErrors();
        $this->assertSame(0, Expense::count());
    }

    public function test_eliminare_un_task_con_sotto_task_senza_conferma_non_elimina_nulla(): void
    {
        $figlio = Task::create(['user_id' => $this->u->id, 'title' => 'Grondaie', 'stage_id' => $this->task->stage_id, 'parent_task_id' => $this->task->id]);
        $s = $this->server();

        $s->tool(Elimina::class, ['entita' => 'task', 'id' => $this->task->id])->assertHasErrors(['1 sotto-task']);
        $s->tool(Elimina::class, ['entita' => 'task', 'id' => $this->task->id, 'con_sottotask' => true])->assertOk()->assertSee('serve conferma');
        $this->assertSame(2, Task::count());

        $s->tool(Elimina::class, ['entita' => 'task', 'id' => $this->task->id, 'con_sottotask' => true, 'conferma' => true])->assertOk();
        $this->assertSame(0, Task::count());

        $s->tool(Ripristina::class, ['entita' => 'task', 'id' => $figlio->id])->assertOk()->assertSee('Grondaie');
    }

    public function test_dall_interfaccia_aggiornamenti_e_spese_passano_dalle_action(): void
    {
        $this->actingAs($this->u);
        Livewire::test(TaskDetail::class, ['task' => $this->task])
            ->set('newUpdateContent', 'Chiamato il geometra')->call('addUpdate')
            ->call('newExpense')->set('expDescription', 'Sopralluogo')->set('expAmount', '80')->call('saveExpense');

        $this->assertSame(2, RegistroScrittura::where('porta', 'interfaccia')->whereIn('entita', ['aggiornamento', 'spesa'])->count());

        Livewire::test(TaskDetail::class, ['task' => $this->task->fresh()])->call('deleteUpdate', TaskUpdate::first()->id);
        $this->assertSoftDeleted('task_updates', ['id' => TaskUpdate::withTrashed()->first()->id]);
    }
}
