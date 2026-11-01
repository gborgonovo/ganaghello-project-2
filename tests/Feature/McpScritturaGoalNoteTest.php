<?php

namespace Tests\Feature;

use App\Mcp\Servers\BigLogServer;
use App\Mcp\Tools\CreaGoal;
use App\Mcp\Tools\CreaNota;
use App\Mcp\Tools\Elimina;
use App\Mcp\Tools\ModificaGoal;
use App\Mcp\Tools\ModificaNota;
use App\Mcp\Tools\Ripristina;
use App\Models\Goal;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class McpScritturaGoalNoteTest extends TestCase
{
    use RefreshDatabase;

    private function server()
    {
        config(['services.mnemosyne.sync' => false]);
        $u = User::factory()->create();
        Sanctum::actingAs($u, ['mcp:leggi', 'mcp:scrivere']);

        return BigLogServer::actingAs($u);
    }

    public function test_goal_creato_modificato_senza_cicli_eliminato_e_ripristinato(): void
    {
        $s = $this->server();
        $s->tool(CreaGoal::class, ['titolo' => 'Aprire il B&B', 'scadenza' => '2028-04-01'])->assertOk()->assertSee('"stato":"active"');
        $padre = Goal::first();
        $s->tool(CreaGoal::class, ['titolo' => 'Tre camere', 'padre_id' => $padre->id])->assertOk();
        $figlio = Goal::where('title', 'Tre camere')->first();

        $s->tool(ModificaGoal::class, ['id' => $padre->id, 'padre_id' => $figlio->id])->assertHasErrors(['ciclo']);
        $s->tool(ModificaGoal::class, ['id' => $figlio->id, 'stato' => 'raggiunto'])->assertOk();
        $this->assertSame('raggiunto', $figlio->fresh()->status);

        $s->tool(ModificaGoal::class, ['id' => $figlio->id, 'stato' => 'finito'])->assertHasErrors();

        $s->tool(Elimina::class, ['entita' => 'goal', 'id' => $figlio->id])->assertOk();
        $this->assertSoftDeleted('goals', ['id' => $figlio->id]);
        $s->tool(Ripristina::class, ['entita' => 'goal', 'id' => $figlio->id])->assertOk();
    }

    public function test_nota_creata_modificata_ed_eliminata(): void
    {
        $s = $this->server();
        $s->tool(CreaNota::class, ['titolo' => 'Misure', 'testo' => 'lavello 120'])->assertOk()->assertSee('lavello 120');
        $n = Note::first();

        $s->tool(ModificaNota::class, ['id' => $n->id, 'testo' => 'lavello 140'])->assertOk();
        $this->assertSame(['Misure', 'lavello 140'], [$n->fresh()->title, $n->fresh()->content]);

        $s->tool(CreaNota::class, ['titolo' => 'Vuota', 'testo' => ' '])->assertHasErrors();

        $s->tool(Elimina::class, ['entita' => 'nota', 'id' => $n->id])->assertOk();
        $this->assertSoftDeleted('notes', ['id' => $n->id]);
    }
}
