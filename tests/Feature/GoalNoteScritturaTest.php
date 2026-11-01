<?php

namespace Tests\Feature;

use App\Livewire\GoalList;
use App\Livewire\NoteIndex;
use App\Livewire\NoteShow;
use App\Models\Area;
use App\Models\Goal;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Le scritture di goal e note dall'interfaccia, prima e dopo il passaggio alle action. */
class GoalNoteScritturaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.mnemosyne.sync' => false]);
        $this->actingAs(User::factory()->create());
    }

    public function test_creare_un_sotto_goal_lo_mette_in_coda_ai_fratelli(): void
    {
        $padre = Goal::create(['user_id' => auth()->id(), 'title' => 'B&B', 'status' => 'active']);
        Goal::create(['user_id' => auth()->id(), 'title' => 'Primo', 'status' => 'active', 'parent_goal_id' => $padre->id, 'sequence' => 4]);

        Livewire::test(GoalList::class)
            ->set('newTitle', 'Camere')->set('newDesc', '  tre camere  ')->set('newDeadline', '2027-06-01')->set('newParentId', $padre->id)
            ->call('createGoal')->assertHasNoErrors();

        $g = Goal::where('title', 'Camere')->first();
        $this->assertSame(5, $g->sequence);
        $this->assertSame('tre camere', $g->description);
        $this->assertSame('active', $g->status);
        $this->assertSame($padre->id, $g->parent_goal_id);
    }

    public function test_modificare_e_eliminare_un_goal(): void
    {
        $g = Goal::create(['user_id' => auth()->id(), 'title' => 'Vecchio', 'status' => 'active']);

        Livewire::test(GoalList::class)->call('startEdit', $g->id)->set('editTitle', 'Nuovo')->set('editStatus', 'raggiunto')->call('saveEdit');
        $this->assertSame(['Nuovo', 'raggiunto'], [$g->fresh()->title, $g->fresh()->status]);

        Livewire::test(GoalList::class)->call('deleteGoal', $g->id);
        $this->assertSoftDeleted('goals', ['id' => $g->id]);
    }

    public function test_il_goal_senza_titolo_e_un_errore(): void
    {
        Livewire::test(GoalList::class)->set('newTitle', '')->call('createGoal')->assertHasErrors('newTitle');
        $this->assertSame(0, Goal::count());
    }

    public function test_creare_modificare_ed_eliminare_una_nota(): void
    {
        $area = Area::create(['name' => 'Cucina']);

        Livewire::test(NoteIndex::class)->set('newTitle', '  Misure ')->set('newContent', ' lavello 120 ')->set('newAreaId', $area->id)->call('create');
        $n = Note::first();
        $this->assertSame(['Misure', 'lavello 120', $area->id], [$n->title, $n->content, $n->area_id]);

        Livewire::test(NoteIndex::class)->call('startEdit', $n->id)->set('editContent', 'lavello 140')->call('saveEdit');
        $this->assertSame('lavello 140', $n->fresh()->content);

        Livewire::test(NoteShow::class, ['note' => $n->fresh()])->call('openEdit')->set('modalTitle', '')->set('modalContent', 'lavello 150')->call('saveModal');
        $this->assertSame([null, 'lavello 150'], [$n->fresh()->title, $n->fresh()->content]);

        Livewire::test(NoteIndex::class)->call('delete', $n->id);
        $this->assertSoftDeleted('notes', ['id' => $n->id]);
    }
}
