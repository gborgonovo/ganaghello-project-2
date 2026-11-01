<?php

namespace Tests\Feature;

use App\Mcp\Servers\BigLogServer;
use App\Mcp\Tools\ComponiPost;
use App\Mcp\Tools\Elimina;
use App\Mcp\Tools\ModificaPost;
use App\Mcp\Tools\Ripristina;
use App\Models\Entry;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Parte B, B2: i post dal server MCP, mai pubblici. */
class McpScritturaPostTest extends TestCase
{
    use RefreshDatabase;

    private User $u;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.mnemosyne.sync' => false]);
        $this->u = User::factory()->create();
    }

    private function server()
    {
        Sanctum::actingAs($this->u, ['mcp:leggi', 'mcp:scrivere']);

        return BigLogServer::actingAs($this->u);
    }

    public function test_componi_post_crea_una_bozza_dalle_voci(): void
    {
        $a = Entry::create(['user_id' => $this->u->id, 'title' => 'Uno', 'content' => 'Primo.', 'kind' => 'postit', 'entry_date' => '2026-10-11']);
        $b = Entry::create(['user_id' => $this->u->id, 'title' => 'Due', 'content' => 'Secondo.', 'kind' => 'postit', 'entry_date' => '2026-10-12']);

        $this->server()->tool(ComponiPost::class, ['voce_ids' => [$b->id, $a->id], 'titolo' => 'Il weekend dell\'orto'])
            ->assertOk()->assertSee(['"visibilita":"draft"', 'Il weekend dell']);

        $p = Post::first();
        $this->assertSame("Primo.\n\nSecondo.", $p->content);
        $this->assertSame('draft', $p->visibility);
    }

    public function test_modifica_post_non_pubblica_e_non_tocca_un_post_pubblico(): void
    {
        $bozza    = Post::create(['user_id' => $this->u->id, 'title' => 'Bozza', 'slug' => 'bozza-abcdefgh', 'content' => '', 'visibility' => 'draft']);
        $pubblico = Post::create(['user_id' => $this->u->id, 'title' => 'Online', 'slug' => 'online', 'content' => 'x', 'visibility' => 'public', 'published_at' => now()]);
        $s = $this->server();

        $s->tool(ModificaPost::class, ['id' => $bozza->id, 'visibilita' => 'public'])->assertHasErrors();
        $this->assertSame('draft', $bozza->fresh()->visibility);

        $s->tool(ModificaPost::class, ['id' => $pubblico->id, 'testo' => 'cambiato'])->assertHasErrors(['pubblicato']);
        $this->assertSame('x', $pubblico->fresh()->content);

        $s->tool(ModificaPost::class, ['id' => $bozza->id, 'titolo' => 'Il tetto', 'visibilita' => 'private'])->assertOk();
        $bozza->refresh();
        $this->assertSame('il-tetto', $bozza->slug);
        $this->assertSame('private', $bozza->visibility);
        $this->assertNull($bozza->published_at);
    }

    public function test_un_post_pubblico_non_si_elimina_dall_mcp_una_bozza_si(): void
    {
        $pubblico = Post::create(['user_id' => $this->u->id, 'title' => 'Online', 'slug' => 'online', 'content' => 'x', 'visibility' => 'public']);
        $bozza    = Post::create(['user_id' => $this->u->id, 'title' => 'Bozza', 'slug' => 'b', 'content' => '', 'visibility' => 'draft']);
        $s = $this->server();

        $s->tool(Elimina::class, ['entita' => 'post', 'id' => $pubblico->id])->assertHasErrors(['pubblicato']);
        $this->assertNotSoftDeleted('posts', ['id' => $pubblico->id]);

        $s->tool(Elimina::class, ['entita' => 'post', 'id' => $bozza->id])->assertOk();
        $this->assertSoftDeleted('posts', ['id' => $bozza->id]);
        $s->tool(Ripristina::class, ['entita' => 'post', 'id' => $bozza->id])->assertOk();
        $this->assertNotSoftDeleted('posts', ['id' => $bozza->id]);
    }
}
