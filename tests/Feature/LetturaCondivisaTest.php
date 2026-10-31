<?php

namespace Tests\Feature;

use App\Livewire\Cruscotto;
use App\Livewire\GlobalSearch;
use App\Models\Entry;
use App\Models\Inspiration;
use App\Models\Note;
use App\Models\Post;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fotografa ricerca globale e scadenze del cruscotto prima e dopo l'estrazione
 * in app/Queries (Fase 6 del piano MCP): l'interfaccia deve restare identica.
 */
class LetturaCondivisaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Stage $todo;
    private Stage $done;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response([], 500)]);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->todo = Stage::create(['code' => 'todo', 'label' => 'Da fare', 'sequence' => 4]);
        $this->done = Stage::create(['code' => 'done', 'label' => 'Fatto', 'sequence' => 7]);
    }

    private function task(array $attrs): Task
    {
        return Task::create($attrs + ['user_id' => $this->user->id, 'stage_id' => $this->todo->id]);
    }

    public function test_la_ricerca_globale_trova_e_raggruppa_per_tipo(): void
    {
        $this->task(['title' => 'Tetto della stalla']);
        $this->task(['title' => 'Altro', 'description' => 'tegole della stalla']);
        $this->task(['title' => 'Niente a che vedere']);
        Entry::create(['user_id' => $this->user->id, 'title' => 'Giornata', 'content' => 'Oggi in stalla', 'kind' => 'postit', 'entry_date' => '2026-10-01']);
        Note::create(['user_id' => $this->user->id, 'title' => 'Stalla', 'content' => 'misure']);
        Inspiration::create(['user_id' => $this->user->id, 'title' => 'Porta da stalla']);
        Post::create(['user_id' => $this->user->id, 'title' => 'La stalla', 'slug' => 'la-stalla', 'content' => '', 'visibility' => 'draft']);

        $results = Livewire::test(GlobalSearch::class)
            ->set('query', 'stalla')
            ->viewData('results');

        $this->assertSame(['Task', 'Diario', 'Note', 'Moodboard', 'Post'], array_keys($results));
        $this->assertCount(2, $results['Task']);
        $this->assertSame('Da fare', $results['Task'][0]['sub']);
        $this->assertSame('Oggi in stalla', $results['Diario'][0]['label']);
        $this->assertSame('draft', $results['Post'][0]['sub']);
        $this->assertNull($results['Post'][0]['url']);
    }

    public function test_la_ricerca_globale_rispetta_i_limiti_per_tipo(): void
    {
        foreach (range(1, 7) as $i) {
            $this->task(['title' => "Cantiere {$i}"]);
        }

        $results = Livewire::test(GlobalSearch::class)->set('query', 'cantiere')->viewData('results');

        $this->assertCount(5, $results['Task']);
    }

    public function test_la_ricerca_globale_ignora_meno_di_due_caratteri(): void
    {
        $this->task(['title' => 'a']);

        $this->assertSame([], Livewire::test(GlobalSearch::class)->set('query', 'a')->viewData('results'));
    }

    public function test_le_scadenze_del_cruscotto_sono_le_prossime_cinque_non_chiuse(): void
    {
        Carbon::setTestNow('2026-10-31 10:00:00');

        $ieri   = $this->task(['title' => 'scaduto', 'due_date' => '2026-10-30']);
        $chiuso = $this->task(['title' => 'chiuso', 'due_date' => '2026-11-02', 'stage_id' => $this->done->id, 'completed_at' => now()]);
        $tardi  = $this->task(['title' => 'tra un mese', 'due_date' => '2026-11-30']);
        $attesi = collect(range(1, 6))->map(fn ($i) => $this->task(['title' => "s{$i}", 'due_date' => "2026-11-0{$i}"]));

        $ids = Livewire::test(Cruscotto::class)->viewData('scadenze')->pluck('id')->all();

        $this->assertSame($attesi->take(5)->pluck('id')->all(), $ids);
        $this->assertNotContains($ieri->id, $ids);
        $this->assertNotContains($chiuso->id, $ids);
        $this->assertNotContains($tardi->id, $ids);
    }
}
