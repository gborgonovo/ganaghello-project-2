<?php

namespace Tests\Feature;

use App\Mcp\Servers\BigLogServer;
use App\Mcp\Tools\CreaVoce;
use App\Mcp\Tools\Elimina;
use App\Mcp\Tools\ModificaVoce;
use App\Mcp\Tools\Ripristina;
use App\Models\Area;
use App\Models\Entry;
use App\Models\RegistroScrittura;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Parte B, passo 3: le voci di diario dettate dall'MCP. */
class McpScritturaVociTest extends TestCase
{
    use RefreshDatabase;

    private User $u;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-01 17:20:00', 'Europe/Rome'));
        config(['services.mnemosyne.sync' => false]);
        $this->u = User::factory()->create();
    }

    private function server()
    {
        Sanctum::actingAs($this->u, ['mcp:leggi', 'mcp:scrivere']);

        return BigLogServer::actingAs($this->u);
    }

    public function test_voce_dettata_con_foto_in_arrivo_nasce_polaroid_con_il_link_per_la_foto(): void
    {
        $area = Area::create(['name' => 'Stalla']);

        $r = $this->server()->tool(CreaVoce::class, [
            'titolo' => 'Il tetto della stalla',
            'testo' => 'Oggi siamo andati su con Lucia. Il geometra dice che le tegole sono da cambiare quasi tutte.',
            'area_id' => $area->id, 'foto_in_arrivo' => true,
        ]);

        $v = Entry::first();
        $r->assertOk()->assertSee(['"azione":"creata"', 'Il tetto della stalla', '"url_aggiungi_foto":"' . route('diario.edit', $v->id) . '"']);
        $this->assertSame('polaroid', $v->kind);
        $this->assertSame('2026-11-01', $v->entry_date->toDateString());
        $this->assertSame('17:20', substr($v->entry_time, 0, 5));
        $this->assertSame('mcp', RegistroScrittura::first()->porta);
    }

    public function test_voce_senza_foto_e_di_un_altro_giorno(): void
    {
        $this->server()->tool(CreaVoce::class, ['testo' => 'Ieri abbiamo potato il noce.', 'data' => '2026-10-31'])
            ->assertOk()->assertDontSee('url_aggiungi_foto');

        $v = Entry::first();
        $this->assertSame('postit', $v->kind);
        $this->assertSame('2026-10-31', $v->entry_date->toDateString());
        $this->assertNull($v->entry_time);
    }

    public function test_il_testo_viene_salvato_esattamente_com_e_passato(): void
    {
        $testo = "Oggi siamo andati su con Lucia, arrivati verso le dieci.\n\nPoi abbiamo mangiato sotto il porticato.";
        $this->server()->tool(CreaVoce::class, ['testo' => $testo]);

        $this->assertSame($testo, Entry::first()->content);
    }

    public function test_voce_collegata_a_un_task(): void
    {
        $stage = Stage::create(['code' => 'idea', 'label' => 'Idea', 'sequence' => 1]);
        $task  = Task::create(['user_id' => $this->u->id, 'title' => 'Tetto', 'stage_id' => $stage->id]);

        $this->server()->tool(CreaVoce::class, ['testo' => 'Sopralluogo sul tetto.', 'task_ids' => [$task->id]])
            ->assertOk()->assertSee('"titolo":"Tetto"');
        $this->assertSame([$task->id], Entry::first()->tasks->pluck('id')->all());
    }

    public function test_voce_senza_testo_e_un_errore(): void
    {
        $this->server()->tool(CreaVoce::class, ['testo' => '  ', 'titolo' => 'Solo titolo'])->assertHasErrors();
        $this->assertSame(0, Entry::count());
    }

    public function test_modifica_voce_corregge_solo_il_testo_e_mantiene_il_link_per_la_foto(): void
    {
        $s = $this->server();
        $s->tool(CreaVoce::class, ['titolo' => 'Il pozzo', 'testo' => 'Pulito il pozo.', 'foto_in_arrivo' => true]);
        $v = Entry::first();

        $s->tool(ModificaVoce::class, ['id' => $v->id, 'testo' => 'Pulito il pozzo.'])
            ->assertOk()->assertSee(['Pulito il pozzo.', 'url_aggiungi_foto']);

        $v->refresh();
        $this->assertSame('Il pozzo', $v->title);
        $this->assertSame('polaroid', $v->kind);
    }

    public function test_eliminare_e_ripristinare_una_voce(): void
    {
        $s = $this->server();
        $s->tool(CreaVoce::class, ['testo' => 'Da togliere.']);
        $v = Entry::first();

        $s->tool(Elimina::class, ['entita' => 'voce', 'id' => $v->id])->assertOk();
        $this->assertSoftDeleted('entries', ['id' => $v->id]);

        $s->tool(Ripristina::class, ['entita' => 'voce', 'id' => $v->id])->assertOk()->assertSee('Da togliere.');
    }

    public function test_la_descrizione_di_crea_voce_porta_le_regole_decise(): void
    {
        $descrizione = (new CreaVoce)->toArray()['description'];

        foreach (['pulizia del parlato', 'Non aggiungere fatti', 'titolo breve', 'oggi e adesso', 'foto', 'salva solo dopo il suo ok'] as $regola) {
            $this->assertStringContainsString($regola, $descrizione);
        }
    }
}
