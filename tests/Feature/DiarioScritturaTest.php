<?php

namespace Tests\Feature;

use App\Jobs\SyncEntryToMnemosyne;
use App\Livewire\AreaDetail;
use App\Livewire\DiarioEditor;
use App\Livewire\DiarioIndex;
use App\Livewire\DiarioShow;
use App\Models\Area;
use App\Models\Entry;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

/** Le scritture delle voci di diario dall'interfaccia, prima e dopo il passaggio a VoceActions. */
class DiarioScritturaTest extends TestCase
{
    use RefreshDatabase;

    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-01 11:42:00', 'Europe/Rome'));
        config(['services.mnemosyne.sync' => false]);
        $u = User::factory()->create();
        $this->actingAs($u);
        $idea = Stage::create(['code' => 'idea', 'label' => 'Idea', 'sequence' => 1]);
        $this->task = Task::create(['user_id' => $u->id, 'title' => 'Tetto', 'stage_id' => $idea->id]);
    }

    public function test_l_editor_crea_una_voce_breve_come_postit_con_data_e_ora(): void
    {
        $area = Area::create(['name' => 'Stalla']);

        Livewire::test(DiarioEditor::class)
            ->set('title', 'Le tegole')->set('content', 'Arrivate le tegole.')
            ->set('areaId', $area->id)->call('toggleTask', $this->task->id)
            ->call('save')->assertHasNoErrors();

        $v = Entry::first();
        $this->assertSame('postit', $v->kind);
        $this->assertSame('2026-11-01', $v->entry_date->toDateString());
        $this->assertSame('11:42', substr($v->entry_time, 0, 5));
        $this->assertSame($area->id, $v->area_id);
        $this->assertSame([$this->task->id], $v->tasks->pluck('id')->all());
    }

    public function test_l_editor_crea_una_voce_lunga_come_nota(): void
    {
        Livewire::test(DiarioEditor::class)
            ->set('title', 'Lunga')->set('content', str_repeat('parole ', 60))
            ->call('save');

        $this->assertSame('nota', Entry::first()->kind);
    }

    public function test_l_editor_richiede_il_titolo(): void
    {
        Livewire::test(DiarioEditor::class)->set('content', 'testo')->call('save')->assertHasErrors('title');
        $this->assertSame(0, Entry::count());
    }

    public function test_l_editor_modifica_mantiene_il_tipo_scelto(): void
    {
        $v = Entry::create(['user_id' => auth()->id(), 'title' => 'T', 'content' => 'breve', 'kind' => 'nota', 'entry_date' => '2026-10-30']);

        Livewire::test(DiarioEditor::class, ['entry' => $v])
            ->set('content', 'ancora breve')->call('save')->assertHasNoErrors();

        $v->refresh();
        $this->assertSame('ancora breve', $v->content);
        $this->assertSame('nota', $v->kind);
        $this->assertSame('2026-10-30', $v->entry_date->toDateString());
    }

    public function test_cambiare_solo_i_task_collegati_risincronizza_la_voce(): void
    {
        config(['services.mnemosyne.sync' => true]);
        $v = Entry::create(['user_id' => auth()->id(), 'title' => 'T', 'content' => 'x', 'kind' => 'postit', 'entry_date' => '2026-10-30']);
        Queue::fake();

        Livewire::test(DiarioEditor::class, ['entry' => $v])->call('toggleTask', $this->task->id)->call('save');

        Queue::assertPushed(SyncEntryToMnemosyne::class);
    }

    public function test_l_annotazione_rapida_dall_area_crea_una_voce_di_oggi(): void
    {
        $area = Area::create(['name' => 'Stalla']);

        Livewire::test(AreaDetail::class, ['area' => $area])
            ->set('entryContent', 'Spazzato il fienile')->call('saveEntry');

        $v = Entry::first();
        $this->assertSame('Spazzato il fienile', $v->content);
        $this->assertSame($area->id, $v->area_id);
        $this->assertSame('2026-11-01', $v->entry_date->toDateString());
    }

    public function test_una_polaroid_senza_foto_mostra_il_segnaposto_e_il_link(): void
    {
        $polaroid = Entry::create(['user_id' => auth()->id(), 'title' => 'Il pozzo', 'content' => 'x', 'kind' => 'polaroid', 'entry_date' => '2026-10-30']);
        $postit   = Entry::create(['user_id' => auth()->id(), 'title' => 'Un post-it', 'content' => 'y', 'kind' => 'postit', 'entry_date' => '2026-10-29']);

        Livewire::test(DiarioIndex::class)
            ->assertSeeInOrder(['Il pozzo'])->assertSee('foto da aggiungere')
            ->call('openRead', $polaroid->id)->assertSee('Aggiungi la foto')->assertSee(route('diario.edit', $polaroid->id), false);

        Livewire::test(DiarioShow::class, ['entry' => $polaroid])->assertSee('Aggiungi la foto');
        Livewire::test(DiarioShow::class, ['entry' => $postit])->assertDontSee('foto da aggiungere');
    }
}
