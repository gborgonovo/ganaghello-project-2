<?php

namespace Tests\Feature;

use App\Actions\TagActions;
use App\Livewire\Settings\TagSettings;
use App\Livewire\TaskForm;
use App\Models\Stage;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** I tag: gestione dalle impostazioni, eliminazione recuperabile, nome unico anche dopo l'eliminazione. */
class TagScritturaTest extends TestCase
{
    use RefreshDatabase;

    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.mnemosyne.sync' => false]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $stage = Stage::create(['code' => 'idea', 'label' => 'Idea', 'sequence' => 1]);
        $this->task = Task::create(['user_id' => auth()->id(), 'title' => 'Tetto', 'stage_id' => $stage->id]);
    }

    public function test_dalle_impostazioni_si_crea_e_un_doppione_e_un_errore(): void
    {
        Livewire::test(TagSettings::class)->set('newName', 'Urgente')->set('newColor', '#AA0000')->call('create')->assertHasNoErrors();
        $this->assertSame(['urgente'], Tag::pluck('name')->all());

        Livewire::test(TagSettings::class)->set('newName', 'urgente')->call('create')->assertHasErrors('newName');
        $this->assertSame(1, Tag::count());
    }

    public function test_eliminare_un_tag_lo_toglie_dagli_elementi_e_ripristinarlo_li_riporta(): void
    {
        $tag = Tag::create(['name' => 'urgente']);
        $this->task->tags()->attach($tag);

        Livewire::test(TagSettings::class)->call('delete', $tag->id);

        $this->assertSoftDeleted('tags', ['id' => $tag->id]);
        $this->assertSame([], $this->task->tags()->pluck('name')->all());

        app(TagActions::class)->ripristina(null, Tag::withTrashed()->find($tag->id));
        $this->assertSame(['urgente'], $this->task->tags()->pluck('name')->all());
    }

    public function test_ricreare_un_tag_eliminato_lo_ripristina_invece_di_fallire(): void
    {
        $tag = Tag::create(['name' => 'urgente']);
        $this->task->tags()->attach($tag);
        $tag->delete();

        Livewire::test(TagSettings::class)->set('newName', 'Urgente')->call('create')->assertHasNoErrors();
        $this->assertSame(1, Tag::withTrashed()->count());
        $this->assertNotSoftDeleted('tags', ['id' => $tag->id]);

        $tag->delete();
        app(TagActions::class)->applica(null, $this->task, ['urgente']);
        $this->assertNotSoftDeleted('tags', ['id' => $tag->id]);

        $tag->delete();
        Livewire::test(TaskForm::class)->set('tagInput', 'urgente')->call('addTag')->assertSet('tagIds', [$tag->id]);
    }

    public function test_rinominare_con_il_nome_di_un_altro_tag_e_un_errore(): void
    {
        Tag::create(['name' => 'urgente']);
        $b = Tag::create(['name' => 'tetto']);

        $this->expectException(\App\Exceptions\RegolaDominio::class);
        app(TagActions::class)->modifica(null, $b, ['name' => 'Urgente']);
    }
}
