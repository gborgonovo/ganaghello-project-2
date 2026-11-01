<?php

namespace Tests\Feature;

use App\Mcp\Servers\BigLogServer;
use App\Mcp\Tools\ApplicaTag;
use App\Mcp\Tools\AssegnaTask;
use App\Mcp\Tools\CollegaGoal;
use App\Mcp\Tools\CollegaVoce;
use App\Mcp\Tools\CreaTask;
use App\Mcp\Tools\ModificaTask;
use App\Mcp\Tools\SpostaStage;
use App\Models\Entry;
use App\Models\Goal;
use App\Models\Post;
use App\Models\RegistroScrittura;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Parte B, passo 1: i tool MCP che scrivono i task. */
class McpScritturaTaskTest extends TestCase
{
    use RefreshDatabase;

    private User $u;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-01 10:00:00');
        config(['services.mnemosyne.sync' => false]);
        $this->u = User::factory()->create(['name' => 'Giorgio']);
        foreach (['idea', 'todo', 'doing', 'in_attesa', 'done', 'archiviato'] as $i => $code) {
            Stage::create(['code' => $code, 'label' => ucfirst($code), 'sequence' => $i + 1]);
        }
    }

    private function conScrittura()
    {
        Sanctum::actingAs($this->u, ['mcp:leggi', 'mcp:scrivere']);

        return BigLogServer::actingAs($this->u);
    }

    public function test_con_un_token_di_sola_lettura_i_tool_di_scrittura_non_ci_sono(): void
    {
        Sanctum::actingAs($this->u, ['mcp:leggi']);

        BigLogServer::actingAs($this->u)->tool(CreaTask::class, ['titolo' => 'X'])->assertHasErrors();
        $this->assertSame(0, Task::count());
    }

    public function test_crea_task_dall_mcp_e_lo_registra_come_mcp(): void
    {
        $goal = Goal::create(['user_id' => $this->u->id, 'title' => 'Aprire il B&B', 'status' => 'active']);

        $this->conScrittura()->tool(CreaTask::class, [
            'titolo' => 'Rifare il tetto', 'stage' => 'todo', 'scadenza' => '2026-11-20',
            'priorita' => 'alta', 'tag' => ['Urgente'], 'goal_ids' => [$goal->id],
        ])->assertOk()->assertSee(['"azione":"creato"', 'Rifare il tetto', '"stage":"todo"', 'urgente', 'Aprire il B&B']);

        $t = Task::first();
        $this->assertSame('2026-11-20', $t->due_date->toDateString());
        $this->assertSame('mcp', RegistroScrittura::where('azione', 'crea')->first()->porta);
    }

    public function test_crea_task_in_esecuzione_senza_scadenza_spiega_cosa_manca(): void
    {
        $this->conScrittura()->tool(CreaTask::class, ['titolo' => 'X', 'stage' => 'doing'])
            ->assertHasErrors(['serve una scadenza']);
        $this->assertSame(0, Task::count());
    }

    public function test_modifica_task_cambia_solo_i_campi_passati_e_null_svuota(): void
    {
        $t = Task::create(['user_id' => $this->u->id, 'title' => 'Tetto', 'description' => 'manto', 'priority' => 'bassa', 'stage_id' => Stage::where('code', 'idea')->value('id')]);

        $this->conScrittura()->tool(ModificaTask::class, ['id' => $t->id, 'priorita' => 'alta', 'descrizione' => null])->assertOk();

        $t->refresh();
        $this->assertSame('alta', $t->priority);
        $this->assertNull($t->description);
        $this->assertSame('Tetto', $t->title);
    }

    public function test_sposta_stage_applica_la_regola(): void
    {
        $t = Task::create(['user_id' => $this->u->id, 'title' => 'Tetto', 'stage_id' => Stage::where('code', 'idea')->value('id')]);
        $server = $this->conScrittura();

        $server->tool(SpostaStage::class, ['id' => $t->id, 'stage' => 'doing'])->assertHasErrors(['serve una scadenza']);
        $server->tool(SpostaStage::class, ['id' => $t->id, 'stage' => 'doing', 'scadenza' => '2026-11-10'])->assertOk()->assertSee('spostato da idea a doing');
        $server->tool(SpostaStage::class, ['id' => $t->id, 'stage' => 'done'])->assertOk();

        $this->assertNotNull($t->fresh()->completed_at);
    }

    public function test_assegna_collega_goal_e_voci(): void
    {
        $t     = Task::create(['user_id' => $this->u->id, 'title' => 'Tetto', 'stage_id' => Stage::where('code', 'idea')->value('id')]);
        $altro = User::factory()->create(['name' => 'Lucia']);
        $goal  = Goal::create(['user_id' => $this->u->id, 'title' => 'B&B', 'status' => 'active']);
        $voce  = Entry::create(['user_id' => $this->u->id, 'title' => 'Sopralluogo', 'content' => 'x', 'kind' => 'postit', 'entry_date' => '2026-10-30']);
        $server = $this->conScrittura();

        $server->tool(AssegnaTask::class, ['id' => $t->id, 'responsabile_id' => $altro->id])->assertOk()->assertSee('Lucia');
        $server->tool(CollegaGoal::class, ['id' => $t->id, 'aggiungi' => [$goal->id]])->assertOk()->assertSee('B&B');
        $server->tool(CollegaVoce::class, ['task_id' => $t->id, 'aggiungi' => [$voce->id]])->assertOk()->assertSee('Sopralluogo');
    }

    public function test_applica_tag_non_tocca_un_post_pubblico(): void
    {
        $post = Post::create(['user_id' => $this->u->id, 'title' => 'Online', 'slug' => 'online', 'content' => '', 'visibility' => 'public']);

        $this->conScrittura()->tool(ApplicaTag::class, ['entita' => 'post', 'id' => $post->id, 'aggiungi' => ['x']])
            ->assertHasErrors(['pubblicato']);
        $this->assertSame(0, $post->tags()->count());
    }

    public function test_un_id_inesistente_da_un_errore_leggibile(): void
    {
        $this->conScrittura()->tool(ModificaTask::class, ['id' => 999, 'titolo' => 'X'])->assertHasErrors(['Nessun task con id 999']);
    }
}
