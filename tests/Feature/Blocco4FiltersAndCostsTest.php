<?php

namespace Tests\Feature;

use App\Livewire\AreaDetail;
use App\Livewire\Kanban;
use App\Models\Area;
use App\Models\Stage;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Blocco 4 (rifiniture 07-delta): filtri kanban (tag, scadenza, weekend)
 * e due totali costi Impegnato/Potenziale.
 */
class Blocco4FiltersAndCostsTest extends TestCase
{
    use RefreshDatabase;

    private Stage $todo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->todo = Stage::create(['code' => 'todo', 'label' => 'To do', 'sequence' => 4]);
    }

    public function test_filtro_tag_mostra_solo_i_task_con_quel_tag(): void
    {
        $tag = Tag::create(['name' => 'impianti']);
        $conTag   = Task::create(['user_id' => auth()->id(), 'title' => 'Caldaia', 'stage_id' => $this->todo->id]);
        Task::create(['user_id' => auth()->id(), 'title' => 'Tinteggiatura', 'stage_id' => $this->todo->id]);
        $conTag->tags()->attach($tag);

        Livewire::test(Kanban::class)
            ->set('filterTags', [$tag->id])
            ->assertSee('Caldaia')
            ->assertDontSee('Tinteggiatura');
    }

    public function test_filtro_weekend_mostra_solo_faccio_io_in_scadenza(): void
    {
        Task::create(['user_id' => auth()->id(), 'title' => 'Monto mensola', 'stage_id' => $this->todo->id, 'executor' => 'una_persona', 'due_date' => today()]);
        Task::create(['user_id' => auth()->id(), 'title' => 'Lavori impresa', 'stage_id' => $this->todo->id, 'executor' => 'impresa', 'due_date' => today()]);
        Task::create(['user_id' => auth()->id(), 'title' => 'Mensola lontana', 'stage_id' => $this->todo->id, 'executor' => 'una_persona', 'due_date' => today()->addDays(30)]);

        Livewire::test(Kanban::class)
            ->set('filterWeekend', true)
            ->assertSee('Monto mensola')
            ->assertDontSee('Lavori impresa')
            ->assertDontSee('Mensola lontana');
    }

    public function test_filtro_scadenza_scaduti(): void
    {
        Task::create(['user_id' => auth()->id(), 'title' => 'In ritardo', 'stage_id' => $this->todo->id, 'due_date' => today()->subDay()]);
        Task::create(['user_id' => auth()->id(), 'title' => 'Tra molto', 'stage_id' => $this->todo->id, 'due_date' => today()->addDays(20)]);

        Livewire::test(Kanban::class)
            ->set('filterScadenza', 'scaduti')
            ->assertSee('In ritardo')
            ->assertDontSee('Tra molto');
    }

    public function test_due_totali_costi_area(): void
    {
        $idea      = Stage::create(['code' => 'idea', 'label' => 'Idea', 'sequence' => 1]);
        $approvato = Stage::create(['code' => 'approvato', 'label' => 'Approvato', 'sequence' => 3]);
        $done      = Stage::create(['code' => 'done', 'label' => 'Done', 'sequence' => 7]);

        $area = Area::create(['name' => 'Stalla']);
        Task::create(['user_id' => auth()->id(), 'title' => 'Sogno piscina', 'stage_id' => $idea->id,      'area_id' => $area->id, 'cost_min' => 1000]);
        Task::create(['user_id' => auth()->id(), 'title' => 'Tetto',         'stage_id' => $approvato->id, 'area_id' => $area->id, 'cost_min' => 2000]);
        Task::create(['user_id' => auth()->id(), 'title' => 'Porta',         'stage_id' => $done->id,      'area_id' => $area->id, 'cost_min' => 500]);

        Livewire::test(AreaDetail::class, ['area' => $area])
            ->assertViewHas('costImpegnato', fn ($v) => (float) $v === 2500.0)   // approvato + done
            ->assertViewHas('costPotenziale', fn ($v) => (float) $v === 3500.0); // + idea
    }
}
