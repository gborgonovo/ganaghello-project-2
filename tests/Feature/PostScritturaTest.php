<?php

namespace Tests\Feature;

use App\Livewire\BlogIndex;
use App\Livewire\DiarioIndex;
use App\Livewire\PostEditor;
use App\Models\Area;
use App\Models\Attachment;
use App\Models\Entry;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/** Le scritture dei post dall'interfaccia, prima e dopo il passaggio a PostActions. */
class PostScritturaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-01 12:00:00');
        config(['services.mnemosyne.sync' => false]);
        $this->actingAs(User::factory()->create());
    }

    private function voce(string $data, string $testo, ?int $area = null, ?string $ora = null): Entry
    {
        return Entry::create(['user_id' => auth()->id(), 'title' => "Voce {$data}", 'content' => $testo, 'kind' => 'postit', 'entry_date' => $data, 'entry_time' => $ora, 'area_id' => $area]);
    }

    public function test_comporre_da_piu_voci_unisce_i_testi_in_ordine_e_tiene_l_area_comune(): void
    {
        $area = Area::create(['name' => 'Orto']);
        $b = $this->voce('2026-10-12', 'Secondo giorno.', $area->id);
        $a = $this->voce('2026-10-11', 'Primo giorno.', $area->id);
        $media = Media::create(['user_id' => auth()->id(), 'filename' => 'x.jpg', 'path' => 'media/x/original', 'original_filename' => 'x.jpg', 'mime_type' => 'image/jpeg', 'size' => 1]);
        Attachment::create(['attachable_type' => Entry::class, 'attachable_id' => $a->id, 'media_id' => $media->id, 'sequence' => 1]);

        Livewire::test(DiarioIndex::class)->call('toggleSelecting')->call('toggleSelect', $b->id)->call('toggleSelect', $a->id)->call('composePost');

        $p = Post::first();
        $this->assertSame("Primo giorno.\n\nSecondo giorno.", $p->content);
        $this->assertSame($area->id, $p->area_id);
        $this->assertSame('draft', $p->visibility);
        $this->assertSame('2026-10-12', $p->published_at->toDateString());
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $p->entries->pluck('id')->all());
        $this->assertSame([$media->id], $p->attachments->pluck('media_id')->all());
    }

    public function test_comporre_da_voci_di_aree_diverse_lascia_l_area_vuota(): void
    {
        $a = $this->voce('2026-10-11', 'Uno.', Area::create(['name' => 'A'])->id);
        $b = $this->voce('2026-10-12', 'Due.', Area::create(['name' => 'B'])->id);

        Livewire::test(DiarioIndex::class)->call('toggleSelecting')->call('toggleSelect', $a->id)->call('toggleSelect', $b->id)->call('composePost');

        $this->assertNull(Post::first()->area_id);
    }

    public function test_l_editor_salva_con_slug_unico_e_data_di_pubblicazione_se_pubblico(): void
    {
        Post::create(['user_id' => auth()->id(), 'title' => 'Altro', 'slug' => 'il-tetto', 'content' => '', 'visibility' => 'draft']);
        $p = Post::create(['user_id' => auth()->id(), 'title' => 'Senza titolo', 'slug' => 'bozza-abc12345', 'content' => '', 'visibility' => 'draft']);

        Livewire::test(PostEditor::class, ['post' => $p])
            ->set('title', 'Il tetto')->set('content', 'Testo')->set('visibility', 'public')
            ->call('save')->assertHasNoErrors();

        $p->refresh();
        $this->assertSame('il-tetto-2', $p->slug);
        $this->assertSame('public', $p->visibility);
        $this->assertSame('2026-11-01', $p->published_at->toDateString());
    }

    public function test_l_elenco_del_blog_pubblica_e_imposta_la_data(): void
    {
        $p = Post::create(['user_id' => auth()->id(), 'title' => 'T', 'slug' => 't', 'content' => '', 'visibility' => 'draft']);

        Livewire::test(BlogIndex::class)->call('setVisibility', $p->id, 'public');

        $p->refresh();
        $this->assertSame('public', $p->visibility);
        $this->assertNotNull($p->published_at);
    }

    public function test_l_elenco_del_blog_elimina_in_modo_recuperabile(): void
    {
        $p = Post::create(['user_id' => auth()->id(), 'title' => 'T', 'slug' => 't', 'content' => '', 'visibility' => 'draft']);

        Livewire::test(BlogIndex::class)->call('delete', $p->id);

        $this->assertSoftDeleted('posts', ['id' => $p->id]);
    }

    public function test_l_editor_aggiunge_e_toglie_tag_in_minuscolo(): void
    {
        $p = Post::create(['user_id' => auth()->id(), 'title' => 'T', 'slug' => 't', 'content' => '', 'visibility' => 'draft']);

        $c = Livewire::test(PostEditor::class, ['post' => $p])->set('newTag', 'Orto')->call('addTag');
        $this->assertSame(['orto'], $p->tags()->pluck('name')->all());

        $c->call('removeTag', $p->tags()->first()->id);
        $this->assertSame([], $p->tags()->pluck('name')->all());
    }
}
