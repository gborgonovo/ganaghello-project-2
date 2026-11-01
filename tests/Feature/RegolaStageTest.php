<?php

namespace Tests\Feature;

use App\Livewire\Kanban;
use App\Livewire\TaskDetail;
use App\Livewire\TaskForm;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * La regola unica di stage e scadenza decisa il 2026-10-31 (RICOGNIZIONE-MCP.md):
 * - scadenza obbligatoria per todo, doing, in_attesa; non per done, archiviato e gli stage di decisione;
 * - Kanban e creazione rapida mettono oggi senza chiedere; il dettaglio la chiede; il form la richiede;
 * - la data di chiusura si imposta entrando in done o archiviato se manca, si toglie solo tornando a uno stage aperto.
 */
class RegolaStageTest extends TestCase
{
    use RefreshDatabase;

    private array $s = [];

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-01 09:30:00');
        config(['services.mnemosyne.sync' => false]);
        $this->actingAs(User::factory()->create());
        foreach (['idea', 'discussione', 'approvato', 'todo', 'doing', 'in_attesa', 'done', 'archiviato'] as $i => $code) {
            $this->s[$code] = Stage::create(['code' => $code, 'label' => ucfirst($code), 'sequence' => $i + 1]);
        }
    }

    private function task(string $stage, array $attrs = []): Task
    {
        return Task::create($attrs + ['user_id' => auth()->id(), 'title' => 'T', 'stage_id' => $this->s[$stage]->id]);
    }

    // ---- Kanban ----

    public function test_kanban_verso_todo_senza_scadenza_mette_oggi(): void
    {
        $t = $this->task('idea');
        Livewire::test(Kanban::class)->call('moveTask', $t->id, $this->s['todo']->id);

        $this->assertSame('2026-11-01', $t->fresh()->due_date->toDateString());
    }

    public function test_kanban_verso_done_senza_scadenza_non_la_inventa_e_chiude(): void
    {
        $t = $this->task('doing', ['due_date' => null]);
        Livewire::test(Kanban::class)->call('moveTask', $t->id, $this->s['done']->id);

        $t->refresh();
        $this->assertNull($t->due_date);
        $this->assertNotNull($t->completed_at);
    }

    public function test_kanban_da_done_ad_archiviato_mantiene_la_chiusura(): void
    {
        $t = $this->task('done', ['completed_at' => '2026-10-20 10:00:00']);
        Livewire::test(Kanban::class)->call('moveTask', $t->id, $this->s['archiviato']->id);

        $this->assertSame('2026-10-20', $t->fresh()->completed_at->toDateString());
    }

    public function test_kanban_verso_archiviato_imposta_la_chiusura_se_manca(): void
    {
        $t = $this->task('idea');
        Livewire::test(Kanban::class)->call('moveTask', $t->id, $this->s['archiviato']->id);

        $this->assertNotNull($t->fresh()->completed_at);
    }

    public function test_kanban_riaprire_toglie_la_chiusura(): void
    {
        $t = $this->task('done', ['completed_at' => now(), 'due_date' => '2026-10-30']);
        Livewire::test(Kanban::class)->call('moveTask', $t->id, $this->s['doing']->id);

        $this->assertNull($t->fresh()->completed_at);
    }

    public function test_kanban_creazione_rapida_in_todo_mette_oggi(): void
    {
        Livewire::test(Kanban::class)->set("quickAdd.{$this->s['todo']->id}", 'Nuovo')->call('quickAddTask', $this->s['todo']->id);

        $this->assertSame('2026-11-01', Task::where('title', 'Nuovo')->first()->due_date->toDateString());
    }

    public function test_kanban_creazione_rapida_in_done_non_mette_scadenza(): void
    {
        Livewire::test(Kanban::class)->set("quickAdd.{$this->s['done']->id}", 'Fatto')->call('quickAddTask', $this->s['done']->id);

        $t = Task::where('title', 'Fatto')->first();
        $this->assertNull($t->due_date);
        $this->assertNotNull($t->completed_at);
    }

    // ---- Form di creazione ----

    public function test_form_in_todo_senza_scadenza_da_errore(): void
    {
        Livewire::test(TaskForm::class)
            ->set('title', 'Nuovo')->set('stage_id', $this->s['todo']->id)
            ->call('save')
            ->assertHasErrors('due_date')
            ->assertSet('expanded', true);

        $this->assertSame(0, Task::count());
    }

    public function test_form_in_done_senza_scadenza_va_bene_e_chiude(): void
    {
        Livewire::test(TaskForm::class)
            ->set('title', 'Già fatto')->set('stage_id', $this->s['done']->id)
            ->call('save')
            ->assertHasNoErrors();

        $t = Task::where('title', 'Già fatto')->first();
        $this->assertNull($t->due_date);
        $this->assertNotNull($t->completed_at);
    }

    // ---- Dettaglio ----

    public function test_dettaglio_verso_todo_senza_scadenza_la_chiede(): void
    {
        $t = $this->task('idea');
        Livewire::test(TaskDetail::class, ['task' => $t])
            ->call('setStage', $this->s['todo']->id)
            ->assertSet('showDueDatePrompt', true);

        $this->assertSame($this->s['idea']->id, $t->fresh()->stage_id);
    }

    public function test_dettaglio_con_scadenza_data_sposta(): void
    {
        $t = $this->task('idea');
        Livewire::test(TaskDetail::class, ['task' => $t])
            ->call('setStage', $this->s['todo']->id)
            ->set('due_date', '2026-11-15')
            ->call('confirmDueDate');

        $t->refresh();
        $this->assertSame($this->s['todo']->id, $t->stage_id);
        $this->assertSame('2026-11-15', $t->due_date->toDateString());
    }

    public function test_dettaglio_da_done_ad_archiviato_mantiene_la_chiusura(): void
    {
        $t = $this->task('done', ['completed_at' => '2026-10-20 10:00:00']);
        Livewire::test(TaskDetail::class, ['task' => $t])->call('setStage', $this->s['archiviato']->id);

        $t->refresh();
        $this->assertSame($this->s['archiviato']->id, $t->stage_id);
        $this->assertSame('2026-10-20', $t->completed_at->toDateString());
    }
}
