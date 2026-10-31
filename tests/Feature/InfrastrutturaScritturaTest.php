<?php

namespace Tests\Feature;

use App\Exceptions\RegolaDominio;
use App\Livewire\Settings\Log;
use App\Mcp\Tools\StrumentoScrittura;
use App\Models\RegistroScrittura;
use App\Models\User;
use App\Support\Porta;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

/** Parte B, passo 0: porta, registro delle scritture, base dei tool di scrittura, token. */
class InfrastrutturaScritturaTest extends TestCase
{
    use RefreshDatabase;

    private function strumento(\Closure $corpo): StrumentoScrittura
    {
        return new class($corpo) extends StrumentoScrittura {
            public function __construct(private \Closure $corpo) {}

            protected function esegui(Request $request): Response
            {
                return ($this->corpo)();
            }

            public function schema(JsonSchema $schema): array
            {
                return [];
            }
        };
    }

    public function test_la_porta_predefinita_e_l_interfaccia_e_finisce_nel_registro(): void
    {
        $u = User::factory()->create();

        $r = RegistroScrittura::annota($u, 'crea', 'task', 1, 'Task «Tetto» creato');

        $this->assertSame('interfaccia', $r->porta);
        $this->assertNull($r->token_id);
    }

    public function test_il_registro_tronca_i_testi_lunghi_nelle_modifiche(): void
    {
        $r = RegistroScrittura::annota(null, 'modifica', 'voce', 1, 'x', ['content' => [str_repeat('a', 900), 'breve']]);

        $this->assertLessThanOrEqual(503, mb_strlen($r->fresh()->modifiche['content'][0]));
        $this->assertSame('breve', $r->fresh()->modifiche['content'][1]);
    }

    public function test_un_token_di_sola_lettura_non_vede_ne_usa_i_tool_di_scrittura(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['mcp:leggi']);
        $tool = $this->strumento(fn () => Response::text('scritto'));

        $this->assertFalse($tool->eligibleForRegistration());
        $this->assertTrue($tool->handle(new Request([]))->isError());
    }

    public function test_un_token_con_la_scrittura_usa_il_tool_dalla_porta_mcp(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['mcp:leggi', 'mcp:scrivere']);
        $tool = $this->strumento(fn () => Response::text(app(Porta::class)->corrente()));

        $this->assertTrue($tool->eligibleForRegistration());
        $risposta = $tool->handle(new Request([]));
        $this->assertFalse($risposta->isError());
        $this->assertSame('mcp', (string) $risposta->content());
    }

    public function test_gli_errori_di_dominio_diventano_messaggi_leggibili(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['mcp:leggi', 'mcp:scrivere']);
        $tool = $this->strumento(fn () => throw new RegolaDominio('Serve la scadenza.'));

        $risposta = $tool->handle(new Request([]));
        $this->assertTrue($risposta->isError());
        $this->assertSame('Serve la scadenza.', (string) $risposta->content());
    }

    public function test_il_comando_emette_token_con_la_scrittura(): void
    {
        $u = User::factory()->create();

        $this->artisan('biglog:token', ['nome' => 'x', 'email' => $u->email, '--scrittura' => true])->assertSuccessful();

        $this->assertSame(['mcp:leggi', 'mcp:scrivere'], $u->tokens()->first()->abilities);
    }

    public function test_il_comando_da_e_toglie_la_scrittura_senza_cambiare_il_token(): void
    {
        $u = User::factory()->create();
        $chiaro = $u->createToken('telefono', ['mcp:leggi'])->plainTextToken;
        $hash   = $u->tokens()->first()->token;

        $this->artisan('biglog:token', ['nome' => 'telefono', 'email' => $u->email, '--abilita-scrittura' => true])->assertSuccessful();
        $t = $u->tokens()->first();
        $this->assertSame(['mcp:leggi', 'mcp:scrivere'], $t->abilities);
        $this->assertSame($hash, $t->token);

        $this->artisan('biglog:token', ['nome' => 'telefono', 'email' => $u->email, '--togli-scrittura' => true])->assertSuccessful();
        $this->assertSame(['mcp:leggi'], $u->tokens()->first()->abilities);
        $this->assertNotEmpty($chiaro);
    }

    public function test_il_log_mostra_le_scritture_e_filtra_per_porta(): void
    {
        $u = User::factory()->create(['role' => 'admin']);
        RegistroScrittura::annota($u, 'crea', 'task', 1, 'Creato dall app');
        app(Porta::class)->imposta(Porta::MCP);
        RegistroScrittura::annota($u, 'crea', 'voce', 2, 'Creata da Claude');

        $this->actingAs($u);
        Livewire::test(Log::class)
            ->assertSee('Creato dall app')->assertSee('Creata da Claude')
            ->set('portaScritture', 'mcp')
            ->assertDontSee('Creato dall app')->assertSee('Creata da Claude');
    }
}
