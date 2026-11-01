<?php

namespace Tests\Feature;

use App\Mcp\Servers\BigLogServer;
use App\Mcp\Tools\CreaIspirazione;
use App\Mcp\Tools\CreaTag;
use App\Mcp\Tools\Elimina;
use App\Mcp\Tools\Elenca;
use App\Mcp\Tools\ModificaIspirazione;
use App\Mcp\Tools\ModificaTag;
use App\Mcp\Tools\Ripristina;
use App\Models\Inspiration;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class McpScritturaTagIspirazioniTest extends TestCase
{
    use RefreshDatabase;

    private function server()
    {
        config(['services.mnemosyne.sync' => false]);
        $u = User::factory()->create();
        Sanctum::actingAs($u, ['mcp:leggi', 'mcp:scrivere']);

        return BigLogServer::actingAs($u);
    }

    public function test_tag_creato_rinominato_eliminato_e_ricreato_per_ripristino(): void
    {
        $s = $this->server();
        $s->tool(CreaTag::class, ['nome' => 'Urgente'])->assertOk()->assertSee('"nome":"urgente"');
        $s->tool(CreaTag::class, ['nome' => 'urgente'])->assertHasErrors(['esiste già']);
        $t = Tag::first();

        $s->tool(ModificaTag::class, ['id' => $t->id, 'nome' => 'Priorità'])->assertOk();
        $this->assertSame('priorità', $t->fresh()->name);

        $s->tool(Elimina::class, ['entita' => 'tag', 'id' => $t->id])->assertOk();
        $s->tool(Elenca::class, ['entita' => 'tag'])->assertSee('"totale":0');
        $s->tool(CreaTag::class, ['nome' => 'priorità'])->assertOk();
        $this->assertSame(1, Tag::withTrashed()->count());
    }

    public function test_ispirazione_creata_modificata_ed_eliminata(): void
    {
        $s = $this->server();
        $s->tool(CreaIspirazione::class, [])->assertHasErrors(['almeno un contenuto']);
        $s->tool(CreaIspirazione::class, ['link' => 'non un indirizzo'])->assertHasErrors();

        $s->tool(CreaIspirazione::class, ['titolo' => 'Piastrelle', 'link' => 'https://example.org/p'])->assertOk()->assertSee('Piastrelle');
        $i = Inspiration::first();

        $s->tool(ModificaIspirazione::class, ['id' => $i->id, 'descrizione' => 'colori caldi'])->assertOk();
        $this->assertSame('colori caldi', $i->fresh()->description);

        $s->tool(Elimina::class, ['entita' => 'ispirazione', 'id' => $i->id])->assertOk();
        $this->assertSoftDeleted('inspirations', ['id' => $i->id]);
        $s->tool(Ripristina::class, ['entita' => 'ispirazione', 'id' => $i->id])->assertOk();
    }
}
