<?php

namespace Tests\Feature;

use App\Livewire\DiarioShow;
use App\Models\Entry;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Blocco 3: dalla pagina di una voce di diario si compone un post (bozza),
 * con il pivot entry_post a tenere la tracciabilita'.
 */
class DiarioComposeTest extends TestCase
{
    use RefreshDatabase;

    public function test_compose_post_da_voce_crea_bozza_collegata(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $entry = Entry::create([
            'user_id'    => $user->id,
            'title'      => 'Weekend orto',
            'content'    => "Sistemato l'orto con mio fratello.",
            'entry_date' => today(),
        ]);

        Livewire::test(DiarioShow::class, ['entry' => $entry])
            ->call('composePost')
            ->assertRedirect();

        $post = Post::first();
        $this->assertNotNull($post, 'Il post deve essere creato');
        $this->assertSame('draft', $post->visibility, 'Il post nasce come bozza');
        $this->assertSame('Weekend orto', $post->title);
        $this->assertSame($user->id, $post->user_id);

        // Pivot entry_post: tracciabilita' verso la voce sorgente
        $this->assertTrue(
            $post->entries()->where('entries.id', $entry->id)->exists(),
            'Il pivot entry_post deve collegare il post alla voce'
        );
    }
}
