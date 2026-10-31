<?php

namespace Tests\Feature;

use App\Jobs\SyncAreaToMnemosyne;
use App\Jobs\SyncGoalToMnemosyne;
use App\Livewire\AreaIndex;
use App\Livewire\GoalList;
use App\Livewire\Settings\Aree;
use App\Livewire\Settings\TagSettings;
use App\Models\Area;
use App\Models\Goal;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Le modifiche di goal, aree e tag devono passare dall'istanza del model, non da
 * un update in tabella: altrimenti observer (sync Mnemosyne) e hook del model
 * (tag in minuscolo) non scattano.
 */
class SalvataggioSullIstanzaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.mnemosyne.sync' => true]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_modificare_un_goal_lo_risincronizza(): void
    {
        $goal = Goal::create(['user_id' => auth()->id(), 'title' => 'Vecchio', 'status' => 'active']);
        Queue::fake();

        Livewire::test(GoalList::class)
            ->call('startEdit', $goal->id)
            ->set('editTitle', 'Nuovo')
            ->call('saveEdit');

        $this->assertSame('Nuovo', $goal->fresh()->title);
        Queue::assertPushed(SyncGoalToMnemosyne::class);
    }

    public function test_modificare_un_area_dall_indice_la_risincronizza(): void
    {
        $area = Area::create(['name' => 'Vecchia']);
        Queue::fake();

        Livewire::test(AreaIndex::class)
            ->call('startEdit', $area->id)
            ->set('editName', 'Nuova')
            ->call('saveEdit');

        $this->assertSame('Nuova', $area->fresh()->name);
        Queue::assertPushed(SyncAreaToMnemosyne::class);
    }

    public function test_modificare_un_area_dalle_impostazioni_la_risincronizza(): void
    {
        $area = Area::create(['name' => 'Vecchia']);
        Queue::fake();

        Livewire::test(Aree::class)
            ->call('startEdit', $area->id)
            ->set('editName', 'Nuova')
            ->call('saveEdit');

        $this->assertSame('Nuova', $area->fresh()->name);
        Queue::assertPushed(SyncAreaToMnemosyne::class);
    }

    public function test_rinominare_un_tag_lo_porta_in_minuscolo(): void
    {
        $tag = Tag::create(['name' => 'urgente']);

        Livewire::test(TagSettings::class)
            ->call('startEdit', $tag->id)
            ->set('editName', 'Molto Urgente')
            ->call('saveEdit');

        $this->assertSame('molto urgente', $tag->fresh()->name);
    }
}
