<?php

namespace Tests\Feature;

use App\Livewire\InspirationShow;
use App\Livewire\MoodboardIndex;
use App\Models\Area;
use App\Models\Inspiration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Le scritture delle ispirazioni dalla moodboard, prima e dopo il passaggio a IspirazioneActions. */
class IspirazioneScritturaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.mnemosyne.sync' => false]);
        $this->actingAs(User::factory()->create());
    }

    public function test_dalla_moodboard_si_crea_con_titolo_link_e_area(): void
    {
        $area = Area::create(['name' => 'Cucina']);

        Livewire::test(MoodboardIndex::class)
            ->set('newTitle', 'Piastrelle')->set('newUrl', 'https://example.org/piastrelle')->set('newAreaId', $area->id)
            ->call('create')->assertHasNoErrors();

        $i = Inspiration::first();
        $this->assertSame(['Piastrelle', 'https://example.org/piastrelle', $area->id], [$i->title, $i->url, $i->area_id]);
    }

    public function test_un_ispirazione_vuota_non_si_crea(): void
    {
        Livewire::test(MoodboardIndex::class)->call('create');

        $this->assertSame(0, Inspiration::count());
    }

    public function test_si_modifica_e_si_elimina(): void
    {
        $i = Inspiration::create(['user_id' => auth()->id(), 'title' => 'Vecchio']);

        Livewire::test(InspirationShow::class, ['inspiration' => $i])
            ->set('editTitle', 'Nuovo')->set('editDesc', 'colori caldi')->call('saveEdit');
        $this->assertSame(['Nuovo', 'colori caldi'], [$i->fresh()->title, $i->fresh()->description]);

        Livewire::test(MoodboardIndex::class)->call('delete', $i->id);
        $this->assertSoftDeleted('inspirations', ['id' => $i->id]);
    }
}
