<?php

namespace Tests\Feature;

use App\Livewire\DiarioShow;
use App\Models\Attachment;
use App\Models\Entry;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Si può eliminare una voce di diario dalla sua pagina; i file immagine
 * condivisi con un post (composto da quella voce) non vengono rimossi.
 */
class DiarioDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_elimina_voce_e_torna_al_diario(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $entry = Entry::create(['user_id' => $user->id, 'content' => 'Oggi', 'entry_date' => today()]);

        Livewire::test(DiarioShow::class, ['entry' => $entry])
            ->call('deleteEntry')
            ->assertRedirect(route('diario'));

        $this->assertSoftDeleted('entries', ['id' => $entry->id]);
    }

    public function test_media_condiviso_con_un_post_non_viene_eliminato(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $entry = Entry::create(['user_id' => $user->id, 'content' => 'Weekend', 'entry_date' => today()]);
        $media = Media::create([
            'user_id' => $user->id, 'filename' => 'a.jpg', 'original_filename' => 'a.jpg',
            'path' => 'media/a.jpg', 'mime_type' => 'image/jpeg',
        ]);
        Attachment::create(['attachable_type' => Entry::class, 'attachable_id' => $entry->id, 'media_id' => $media->id, 'sequence' => 0]);

        // Un post composto da questa voce condivide lo stesso media.
        $post = Post::create(['user_id' => $user->id, 'title' => 'P', 'slug' => 'p', 'content' => 'c', 'visibility' => 'draft']);
        Attachment::create(['attachable_type' => Post::class, 'attachable_id' => $post->id, 'media_id' => $media->id, 'sequence' => 0]);

        Livewire::test(DiarioShow::class, ['entry' => $entry])->call('deleteEntry');

        $this->assertSoftDeleted('entries', ['id' => $entry->id]);
        // Il media sopravvive perché il post lo usa ancora.
        $this->assertDatabaseHas('media', ['id' => $media->id]);
        // L'allegato della voce è rimosso, quello del post resta.
        $this->assertDatabaseMissing('attachments', ['attachable_type' => Entry::class, 'attachable_id' => $entry->id]);
        $this->assertDatabaseHas('attachments', ['attachable_type' => Post::class, 'attachable_id' => $post->id, 'media_id' => $media->id]);
    }
}
