<?php

namespace Tests\Feature;

use App\Actions\TaskActions;
use App\Exceptions\RegolaDominio;
use App\Jobs\SyncTaskToMnemosyne;
use App\Models\Goal;
use App\Models\RegistroScrittura;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TaskActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $u;
    private TaskActions $a;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.mnemosyne.sync' => false]);
        $this->u = User::factory()->create();
        $this->a = app(TaskActions::class);
        foreach (['idea', 'todo', 'doing', 'done', 'archiviato'] as $i => $code) {
            Stage::create(['code' => $code, 'label' => ucfirst($code), 'sequence' => $i + 1]);
        }
    }

    public function test_crea_senza_scadenza_in_esecuzione_e_un_errore_se_non_predefinita(): void
    {
        $this->expectException(ValidationException::class);
        $this->a->crea($this->u, ['title' => 'X', 'stage' => 'todo']);
    }

    public function test_crea_con_tag_e_goal_registra_e_risincronizza(): void
    {
        config(['services.mnemosyne.sync' => true]);
        Queue::fake();
        $goal = Goal::create(['user_id' => $this->u->id, 'title' => 'B&B', 'status' => 'active']);

        $t = $this->a->crea($this->u, ['title' => '  Tetto  ', 'tag' => ['Urgente', 'urgente'], 'goal_ids' => [$goal->id]]);

        $this->assertSame('Tetto', $t->title);
        $this->assertSame(['urgente'], $t->tags->pluck('name')->all());
        $this->assertSame([$goal->id], $t->goals->pluck('id')->all());
        Queue::assertPushed(SyncTaskToMnemosyne::class);
        $this->assertTrue(RegistroScrittura::where(['azione' => 'crea', 'entita' => 'task', 'entita_id' => $t->id])->exists());
    }

    public function test_modifica_parziale_registra_prima_e_dopo(): void
    {
        $t = $this->a->crea($this->u, ['title' => 'Vecchio', 'priority' => 'bassa']);

        $this->a->modifica($this->u, $t, ['priority' => 'alta']);

        $r = RegistroScrittura::where('azione', 'modifica')->first();
        $this->assertSame(['bassa', 'alta'], $r->modifiche['priority']);
        $this->assertSame('Vecchio', $t->fresh()->title);
    }

    public function test_togliere_la_scadenza_a_un_task_in_esecuzione_e_un_errore(): void
    {
        $t = $this->a->crea($this->u, ['title' => 'X', 'stage' => 'doing', 'due_date' => '2026-12-01']);

        $this->expectException(ValidationException::class);
        $this->a->modifica($this->u, $t, ['due_date' => '']);
    }

    public function test_costo_minimo_oltre_il_massimo_e_un_errore(): void
    {
        $this->expectException(ValidationException::class);
        $this->a->crea($this->u, ['title' => 'X', 'cost_min' => 500, 'cost_max' => 100]);
    }

    public function test_il_padre_non_puo_creare_un_ciclo(): void
    {
        $nonno  = $this->a->crea($this->u, ['title' => 'Nonno']);
        $padre  = $this->a->crea($this->u, ['title' => 'Padre', 'parent_task_id' => $nonno->id]);
        $figlio = $this->a->crea($this->u, ['title' => 'Figlio', 'parent_task_id' => $padre->id]);

        $this->expectException(RegolaDominio::class);
        $this->a->modifica($this->u, $nonno, ['parent_task_id' => $figlio->id]);
    }

    public function test_eliminare_con_sotto_task_chiede_conferma_o_li_include(): void
    {
        $padre  = $this->a->crea($this->u, ['title' => 'Padre']);
        $figlio = $this->a->crea($this->u, ['title' => 'Figlio', 'parent_task_id' => $padre->id]);
        $nipote = $this->a->crea($this->u, ['title' => 'Nipote', 'parent_task_id' => $figlio->id]);

        try {
            $this->a->elimina($this->u, $padre);
            $this->fail('Doveva rifiutare');
        } catch (RegolaDominio $e) {
            $this->assertStringContainsString('2 sotto-task', $e->getMessage());
        }

        $ids = $this->a->elimina($this->u, $padre, 'includi');
        $this->assertEqualsCanonicalizing([$padre->id, $figlio->id, $nipote->id], $ids);
        $this->assertSame(0, Task::count());

        $this->a->ripristina($this->u, Task::withTrashed()->find($padre->id));
        $this->assertSame(1, Task::count());
    }

    public function test_eliminare_lasciando_i_sotto_task_come_fa_l_interfaccia(): void
    {
        $padre  = $this->a->crea($this->u, ['title' => 'Padre']);
        $figlio = $this->a->crea($this->u, ['title' => 'Figlio', 'parent_task_id' => $padre->id]);

        $this->a->elimina($this->u, $padre, 'lascia');

        $this->assertNotNull(Task::find($figlio->id));
        $this->assertNull(Task::find($padre->id));
    }

    public function test_stage_sconosciuto_e_un_errore_leggibile(): void
    {
        $t = $this->a->crea($this->u, ['title' => 'X']);

        $this->expectException(RegolaDominio::class);
        $this->expectExceptionMessage('Stage sconosciuto: finito');
        $this->a->spostaStage($this->u, $t, 'finito');
    }
}
