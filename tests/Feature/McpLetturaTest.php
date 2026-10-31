<?php

namespace Tests\Feature;

use App\Mcp\Servers\BigLogServer;
use App\Mcp\Tools\Briefing;
use App\Mcp\Tools\Cerca;
use App\Mcp\Tools\CosaScade;
use App\Mcp\Tools\CosaSiRaffredda;
use App\Mcp\Tools\Elenca;
use App\Mcp\Tools\Leggi;
use App\Mcp\Tools\Riferimenti;
use App\Models\Area;
use App\Models\Entry;
use App\Models\Expense;
use App\Models\Goal;
use App\Models\Note;
use App\Models\Stage;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Fase 6 del piano MCP: i tool di lettura. */
class McpLetturaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private array $stage = [];

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-31 10:00:00');
        config([
            'services.mnemosyne.sync'     => false,
            'services.mnemosyne.endpoint' => 'https://mnemosyne.test',
            'services.mnemosyne.api_key'  => 'x',
            'services.mnemosyne.project'  => 'BiG',
            'services.mnemosyne.scope'    => 'Private',
        ]);
        $this->user = User::factory()->create(['name' => 'Giorgio']);
        foreach (['idea', 'discussione', 'approvato', 'todo', 'doing', 'in_attesa', 'done', 'archiviato'] as $i => $code) {
            $this->stage[$code] = Stage::create(['code' => $code, 'label' => ucfirst($code), 'sequence' => $i + 1]);
        }
    }

    private function task(string $titolo, string $stage = 'todo', array $attrs = []): Task
    {
        return Task::create($attrs + ['user_id' => $this->user->id, 'title' => $titolo, 'stage_id' => $this->stage[$stage]->id]);
    }

    private function chiama(string $tool, array $args = [])
    {
        return BigLogServer::actingAs($this->user)->tool($tool, $args);
    }

    public function test_elenca_task_filtra_per_stage_e_aperti(): void
    {
        $this->task('Tetto', 'doing', ['due_date' => '2026-11-05']);
        $this->task('Finestre', 'idea');
        $this->task('Cancello', 'done', ['completed_at' => now()]);

        $this->chiama(Elenca::class, ['entita' => 'task', 'stage' => ['doing']])
            ->assertOk()->assertSee('"totale":1')->assertSee('Tetto')->assertDontSee('Finestre');

        $this->chiama(Elenca::class, ['entita' => 'task', 'aperti' => true])
            ->assertOk()->assertSee('"totale":2')->assertDontSee('Cancello');
    }

    public function test_elenca_task_per_area_con_sotto_aree(): void
    {
        $casa   = Area::create(['name' => 'Casa']);
        $cucina = Area::create(['name' => 'Cucina', 'parent_area_id' => $casa->id]);
        $this->task('Lavello', 'idea', ['area_id' => $cucina->id]);
        $this->task('Facciata', 'idea', ['area_id' => $casa->id]);

        $this->chiama(Elenca::class, ['entita' => 'task', 'area_id' => $casa->id])
            ->assertSee('"totale":1')->assertSee('Facciata');

        $this->chiama(Elenca::class, ['entita' => 'task', 'area_id' => $casa->id, 'includi_sottoaree' => true])
            ->assertSee('"totale":2')->assertSee('Lavello');
    }

    public function test_elenca_task_in_scadenza_e_scaduti(): void
    {
        $this->task('Scaduto', 'todo', ['due_date' => '2026-10-20']);
        $this->task('Presto', 'todo', ['due_date' => '2026-11-03']);
        $this->task('Lontano', 'todo', ['due_date' => '2027-01-10']);

        $this->chiama(Elenca::class, ['entita' => 'task', 'scadenza_entro_giorni' => 7])
            ->assertSee('"totale":1')->assertSee('Presto');

        $this->chiama(Elenca::class, ['entita' => 'task', 'scadenza_entro_giorni' => 7, 'scaduti' => true])
            ->assertSee('"totale":2')->assertSee('Scaduto');
    }

    public function test_un_filtro_che_non_vale_per_l_entita_e_un_errore(): void
    {
        $this->chiama(Elenca::class, ['entita' => 'nota', 'stage' => ['todo']])
            ->assertHasErrors(['Filtri non validi per nota: stage']);
    }

    public function test_gli_aggiornamenti_richiedono_il_task(): void
    {
        $this->chiama(Elenca::class, ['entita' => 'aggiornamento'])
            ->assertHasErrors(['serve il filtro task_id']);

        $t = $this->task('Tetto');
        TaskUpdate::create(['task_id' => $t->id, 'content' => 'Arrivate le tegole']);

        $this->chiama(Elenca::class, ['entita' => 'aggiornamento', 'task_id' => $t->id])
            ->assertOk()->assertSee('Arrivate le tegole');
    }

    public function test_gli_archiviati_sono_esclusi_salvo_richiesta(): void
    {
        $nota = Note::create(['user_id' => $this->user->id, 'title' => 'Vecchia', 'content' => 'x']);
        $nota->delete();

        $this->chiama(Elenca::class, ['entita' => 'nota'])->assertSee('"totale":0');
        $this->chiama(Elenca::class, ['entita' => 'nota', 'includi_archiviati' => true])
            ->assertSee('"totale":1')->assertSee('archiviato_il');

        $this->chiama(Leggi::class, ['entita' => 'nota', 'id' => $nota->id])->assertHasErrors(['includi_archiviati']);
        $this->chiama(Leggi::class, ['entita' => 'nota', 'id' => $nota->id, 'includi_archiviati' => true])->assertOk();
    }

    public function test_le_spese_riportano_la_somma_filtrata(): void
    {
        $t = $this->task('Tetto');
        Expense::create(['task_id' => $t->id, 'description' => 'Tegole', 'amount' => 120.5, 'category' => 'materiali', 'expense_date' => '2026-10-02']);
        Expense::create(['task_id' => $t->id, 'description' => 'Muratore', 'amount' => 300, 'category' => 'manodopera', 'expense_date' => '2026-10-15']);
        Expense::create(['task_id' => $t->id, 'description' => 'Settembre', 'amount' => 50, 'category' => 'materiali', 'expense_date' => '2026-09-10']);

        $this->chiama(Elenca::class, ['entita' => 'spesa', 'dal' => '2026-10-01', 'al' => '2026-10-31'])
            ->assertSee('"totale":2')->assertSee('"somma_importi":420.5');
    }

    public function test_leggi_un_task_con_le_relazioni(): void
    {
        $goal = Goal::create(['user_id' => $this->user->id, 'title' => 'Aprire il B&B', 'status' => 'active']);
        $t    = $this->task('Tetto', 'doing', ['due_date' => '2026-11-05', 'description' => 'Rifare il manto']);
        $t->goals()->attach($goal);
        $t->tags()->attach(Tag::create(['name' => 'urgente']));
        $this->task('Grondaie', 'idea', ['parent_task_id' => $t->id]);
        $voce = Entry::create(['user_id' => $this->user->id, 'title' => 'Sopralluogo', 'content' => 'visto il tetto', 'kind' => 'postit', 'entry_date' => '2026-10-10']);
        $t->entries()->attach($voce);
        Expense::create(['task_id' => $t->id, 'description' => 'Tegole', 'amount' => 100]);

        $this->chiama(Leggi::class, ['entita' => 'task', 'id' => $t->id])
            ->assertOk()
            ->assertSee(['Rifare il manto', 'Aprire il B&B', 'urgente', 'Grondaie', 'Sopralluogo', '"totale_spese":100']);
    }

    public function test_leggi_un_id_inesistente_da_un_errore_leggibile(): void
    {
        $this->chiama(Leggi::class, ['entita' => 'task', 'id' => 999])->assertHasErrors(['Nessun elemento di tipo task con id 999']);
    }

    public function test_cerca_trova_per_tipo(): void
    {
        $this->task('Tetto della stalla');
        Note::create(['user_id' => $this->user->id, 'title' => 'Misure stalla', 'content' => 'x']);

        $this->chiama(Cerca::class, ['testo' => 'stalla'])
            ->assertOk()->assertSee(['"task":', 'Tetto della stalla', '"nota":', 'Misure stalla']);

        $this->chiama(Cerca::class, ['testo' => 'stalla', 'entita' => 'nota'])->assertDontSee('Tetto della stalla');
    }

    public function test_cosa_scade_include_gli_scaduti_per_impostazione(): void
    {
        $this->task('Scaduto', 'todo', ['due_date' => '2026-10-25']);
        $this->task('Presto', 'todo', ['due_date' => '2026-11-02']);

        $this->chiama(CosaScade::class)->assertOk()->assertSee(['Scaduto', 'Presto', '"tra_giorni":-6', '"tra_giorni":2']);
        $this->chiama(CosaScade::class, ['includi_scaduti' => false])->assertDontSee('Scaduto');
    }

    public function test_cosa_si_raffredda_con_mnemosyne_spento_da_errore_esplicito(): void
    {
        Http::fake(['*' => Http::response([], 500)]);

        $this->chiama(CosaSiRaffredda::class)->assertHasErrors(['Mnemosyne non è raggiungibile']);
    }

    public function test_cosa_si_raffredda_arricchisce_i_task_dormienti(): void
    {
        $area = Area::create(['name' => 'Stalla']);
        $this->task('Tetto', 'in_attesa', ['area_id' => $area->id, 'due_date' => '2026-12-01', 'mnemosyne_node_name' => 'big-tetto']);
        Http::fake(['*' => Http::response(['dormant' => [['name' => 'big-tetto', 'properties' => ['folder' => 'BiG']]], 'hot' => []])]);

        $this->chiama(CosaSiRaffredda::class)
            ->assertOk()->assertSee(['"nome":"Tetto"', '"stage":"in_attesa"', '"area":"Stalla"', '"scadenza":"2026-12-01"']);
    }

    public function test_il_briefing_esce_anche_senza_mnemosyne(): void
    {
        Http::fake(['*' => Http::response([], 500)]);
        $this->task('In corso', 'doing', ['due_date' => '2026-11-20']);
        Entry::create(['user_id' => $this->user->id, 'title' => 'Ultima voce', 'content' => 'x', 'kind' => 'postit', 'entry_date' => '2026-10-30']);

        $this->chiama(Briefing::class)
            ->assertOk()->assertSee(['In corso', 'Ultima voce', 'Mnemosyne non è raggiungibile']);
    }

    public function test_riferimenti_espone_stage_aree_e_utenti(): void
    {
        $casa = Area::create(['name' => 'Casa']);
        Area::create(['name' => 'Cucina', 'parent_area_id' => $casa->id]);

        $this->chiama(Riferimenti::class)
            ->assertOk()->assertSee(['"codice":"in_attesa"', '"nome":"Cucina"', '"profondita":1', '"nome":"Giorgio"']);
    }

    public function test_tutti_i_tool_di_lettura_sono_in_sola_lettura(): void
    {
        foreach ([Briefing::class, Riferimenti::class, Elenca::class, Leggi::class, Cerca::class, CosaScade::class, CosaSiRaffredda::class] as $tool) {
            $this->assertTrue((new $tool)->toArray()['annotations']['readOnlyHint'] ?? false, $tool);
        }
    }
}
