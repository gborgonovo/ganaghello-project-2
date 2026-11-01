<?php

namespace Tests\Feature;

use App\Livewire\AreaDetail;
use App\Livewire\AreaIndex;
use App\Livewire\Settings\Aree;
use App\Models\Area;
use App\Models\Note;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Le scritture delle aree dalle tre schermate, prima e dopo il passaggio ad AreaActions. */
class AreaScritturaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.mnemosyne.sync' => false]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_dall_indice_una_nuova_area_va_in_coda(): void
    {
        Area::create(['name' => 'Prima', 'sequence' => 7]);

        Livewire::test(AreaIndex::class)->set('newName', '  Pozzo ')->set('newDesc', 'acqua')->call('createArea')->assertHasNoErrors();

        $a = Area::where('name', 'Pozzo')->first();
        $this->assertSame(8, $a->sequence);
        $this->assertSame('acqua', $a->description);
    }

    public function test_dalle_impostazioni_si_crea_con_i_colori(): void
    {
        Livewire::test(Aree::class)->set('newName', 'Silos')->set('newColor', '#112233')->set('newTextColor', '#FFFFFF')->call('create');

        $a = Area::where('name', 'Silos')->first();
        $this->assertSame(['#112233', '#FFFFFF'], [$a->color, $a->text_color]);
    }

    public function test_dal_dettaglio_si_rinomina_e_si_cambiano_stato_e_colore(): void
    {
        $a = Area::create(['name' => 'Vecchio']);

        Livewire::test(AreaDetail::class, ['area' => $a])
            ->set('areaName', 'Nuovo')->call('saveName')
            ->set('areaStatus', 'cantiere')->call('saveStatus')
            ->set('areaColor', '#445566')->call('saveColor');

        $a->refresh();
        $this->assertSame(['Nuovo', 'cantiere', '#445566'], [$a->name, $a->status, $a->color]);
    }

    public function test_un_area_con_task_non_si_elimina_dall_indice_ne_dalle_impostazioni(): void
    {
        $a = Area::create(['name' => 'Stalla']);
        $stage = Stage::create(['code' => 'idea', 'label' => 'Idea', 'sequence' => 1]);
        Task::create(['user_id' => auth()->id(), 'title' => 'Tetto', 'stage_id' => $stage->id, 'area_id' => $a->id]);

        Livewire::test(AreaIndex::class)->call('deleteArea', $a->id);
        Livewire::test(Aree::class)->call('delete', $a->id)->assertSet('deleteError', 'Usata da 1 elementi: riassegna prima.');

        $this->assertNotNull(Area::find($a->id));
    }

    public function test_un_area_con_sotto_aree_non_si_elimina_dalle_impostazioni(): void
    {
        $a = Area::create(['name' => 'Casa']);
        Area::create(['name' => 'Cucina', 'parent_area_id' => $a->id]);

        Livewire::test(Aree::class)->call('delete', $a->id)->assertSet('deleteError', 'Ha sotto-aree: rimuovile prima.');
        $this->assertNotNull(Area::find($a->id));
    }

    public function test_un_area_vuota_si_elimina(): void
    {
        $a = Area::create(['name' => 'Vuota']);

        Livewire::test(Aree::class)->call('delete', $a->id);

        $this->assertNull(Area::find($a->id));
    }
}
