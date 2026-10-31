<?php

namespace Tests\Feature;

use App\Mcp\Servers\BigLogServer;
use App\Mcp\Tools\Ping;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 5 del piano MCP: la rotta /mcp si apre solo con un token Sanctum che
 * porta la capacità mcp:leggi; il tool di prova risponde; il comando emette
 * e revoca i token.
 */
class McpAuthTest extends TestCase
{
    use RefreshDatabase;

    private function initialize(?string $token = null)
    {
        $headers = ['Accept' => 'application/json, text/event-stream'];
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id'      => 1,
            'method'  => 'initialize',
            'params'  => [
                'protocolVersion' => '2025-06-18',
                'capabilities'    => new \stdClass,
                'clientInfo'      => ['name' => 'test', 'version' => '1'],
            ],
        ], $headers);
    }

    public function test_senza_token_risponde_401_in_json(): void
    {
        $this->initialize()->assertStatus(401);
    }

    public function test_senza_accept_json_non_reindirizza_al_login(): void
    {
        $this->post('/mcp', [])->assertStatus(401);
    }

    public function test_token_senza_la_capacita_di_lettura_e_rifiutato(): void
    {
        $token = User::factory()->create()->createToken('x', ['altro'])->plainTextToken;

        $this->initialize($token)->assertStatus(403);
    }

    public function test_token_con_capacita_di_lettura_apre_la_sessione(): void
    {
        $token = User::factory()->create()->createToken('x', ['mcp:leggi'])->plainTextToken;

        $this->initialize($token)
            ->assertOk()
            ->assertJsonPath('result.serverInfo.name', 'BiG-Log');
    }

    public function test_il_tool_ping_riconosce_l_utente(): void
    {
        $user = User::factory()->create(['name' => 'Giorgio']);

        BigLogServer::actingAs($user)
            ->tool(Ping::class)
            ->assertOk()
            ->assertSee('Giorgio');
    }

    public function test_il_comando_emette_un_token_di_sola_lettura(): void
    {
        $user = User::factory()->create();

        $this->artisan('biglog:token', ['nome' => 'portatile', 'email' => $user->email])
            ->assertSuccessful();

        $token = $user->tokens()->where('name', 'portatile')->first();
        $this->assertNotNull($token);
        $this->assertSame(['mcp:leggi'], $token->abilities);
    }

    public function test_il_comando_revoca_i_token_per_nome(): void
    {
        $user = User::factory()->create();
        $user->createToken('portatile', ['mcp:leggi']);
        $user->createToken('telefono', ['mcp:leggi']);

        $this->artisan('biglog:token', ['nome' => 'portatile', 'email' => $user->email, '--revoca' => true])
            ->assertSuccessful();

        $this->assertSame(['telefono'], $user->tokens()->pluck('name')->all());
    }

    public function test_il_comando_segnala_un_utente_inesistente(): void
    {
        $this->artisan('biglog:token', ['nome' => 'x', 'email' => 'nessuno@example.org'])
            ->assertFailed();
    }
}
