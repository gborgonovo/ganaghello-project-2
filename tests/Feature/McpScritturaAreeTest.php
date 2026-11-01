<?php

namespace Tests\Feature;

use App\Mcp\Servers\BigLogServer;
use App\Mcp\Tools\CreaArea;
use App\Mcp\Tools\Elimina;
use App\Mcp\Tools\ModificaArea;
use App\Mcp\Tools\Ripristina;
use App\Models\Area;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class McpScritturaAreeTest extends TestCase
{
    use RefreshDatabase;

    private User $u;

    private function server()
    {
        config(['services.mnemosyne.sync' => false]);
        $this->u = User::factory()->create();
        Sanctum::actingAs($this->u, ['mcp:leggi', 'mcp:scrivere']);

        return BigLogServer::actingAs($this->u);
    }

    public function test_area_creata_dentro_un_altra_e_senza_cicli(): void
    {
        $s = $this->server();
        $s->tool(CreaArea::class, ['nome' => 'Casa'])->assertOk();
        $casa = Area::first();
        $s->tool(CreaArea::class, ['nome' => 'Cucina', 'padre_id' => $casa->id, 'colore' => '#AA5500'])->assertOk();
        $cucina = Area::where('name', 'Cucina')->first();

        $s->tool(ModificaArea::class, ['id' => $casa->id, 'padre_id' => $cucina->id])->assertHasErrors(['ciclo']);
        $s->tool(ModificaArea::class, ['id' => $cucina->id, 'colore' => 'rosso'])->assertHasErrors();
        $s->tool(ModificaArea::class, ['id' => $cucina->id, 'stato' => 'cantiere'])->assertOk();
        $this->assertSame('cantiere', $cucina->fresh()->status);
    }

    public function test_un_area_in_uso_non_si_elimina_una_vuota_si_e_torna(): void
    {
        $s = $this->server();
        $piena = Area::create(['name' => 'Stalla']);
        Note::create(['user_id' => $this->u->id, 'content' => 'x', 'area_id' => $piena->id]);
        $vuota = Area::create(['name' => 'Vuota']);

        $s->tool(Elimina::class, ['entita' => 'area', 'id' => $piena->id])->assertHasErrors(['riassegna prima']);
        $this->assertNotSoftDeleted('areas', ['id' => $piena->id]);

        $s->tool(Elimina::class, ['entita' => 'area', 'id' => $vuota->id])->assertOk();
        $this->assertSoftDeleted('areas', ['id' => $vuota->id]);
        $s->tool(Ripristina::class, ['entita' => 'area', 'id' => $vuota->id])->assertOk()->assertSee('Vuota');
    }
}
