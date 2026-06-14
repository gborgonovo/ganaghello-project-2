<?php

namespace Tests\Feature;

use App\Livewire\AreaIndex;
use App\Livewire\GoalList;
use App\Livewire\NoteIndex;
use App\Livewire\Settings\TagSettings;
use App\Models\Area;
use App\Models\Goal;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Blocco 2 (coerenza dei gesti): ogni azione discreta di scrittura
 * conferma con un toast; le cancellazioni bloccate emettono un toast d'errore.
 */
class InteractionFeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_creazione_goal_emette_toast(): void
    {
        Livewire::test(GoalList::class)
            ->set('newTitle', 'Avviare il B&B')
            ->call('createGoal')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('goals', ['title' => 'Avviare il B&B']);
    }

    public function test_eliminazione_goal_emette_toast(): void
    {
        $goal = Goal::create(['user_id' => auth()->id(), 'title' => 'Da rimuovere']);

        Livewire::test(GoalList::class)
            ->call('deleteGoal', $goal->id)
            ->assertDispatched('toast');

        $this->assertSoftDeleted('goals', ['id' => $goal->id]);
    }

    public function test_creazione_tag_emette_toast(): void
    {
        Livewire::test(TagSettings::class)
            ->set('newName', 'impianti')
            ->call('create')
            ->assertDispatched('toast');

        $this->assertDatabaseCount('tags', 1);
    }

    public function test_creazione_area_emette_toast(): void
    {
        Livewire::test(AreaIndex::class)
            ->set('newName', 'Stalla')
            ->call('createArea')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('areas', ['name' => 'Stalla']);
    }

    public function test_eliminazione_area_bloccata_emette_toast_errore(): void
    {
        $stage = Stage::create(['code' => 'idea', 'label' => 'Idea']);
        $area  = Area::create(['name' => 'Abitazione']);
        Task::create(['user_id' => auth()->id(), 'title' => 'Un task', 'stage_id' => $stage->id, 'area_id' => $area->id]);

        Livewire::test(AreaIndex::class)
            ->call('deleteArea', $area->id)
            ->assertDispatched('toast', type: 'error');

        // L'area non e' stata eliminata perche' in uso
        $this->assertDatabaseHas('areas', ['id' => $area->id]);
    }

    public function test_creazione_nota_emette_toast(): void
    {
        Livewire::test(NoteIndex::class)
            ->set('newTitle', 'Promemoria')
            ->set('newContent', 'Chiamare il geometra')
            ->call('create')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('notes', ['title' => 'Promemoria']);
    }
}
