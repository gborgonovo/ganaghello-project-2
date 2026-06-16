<?php

namespace App\Livewire;

use App\Models\Attachment;
use App\Models\Entry;
use App\Models\Post;
use App\Services\MediaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Pagina di lettura di una singola voce (permalink: deep-link da Mnemosyne,
 * ricerca, post). La modifica avviene nella pagina dedicata DiarioEditor.
 */
class DiarioShow extends Component
{
    public Entry $entry;

    public function mount(Entry $entry): void
    {
        $this->entry = $entry->load('area', 'attachments.media', 'posts');
    }

    /**
     * Compone un post (bozza) da questa singola voce e apre l'editor del blog.
     * Il pivot entry_post tiene la tracciabilita'; il post nasce sempre come bozza.
     */
    public function composePost()
    {
        $entry = $this->entry->load('attachments.media');

        $post = Post::create([
            'user_id'      => Auth::id(),
            'area_id'      => $entry->area_id,
            'title'        => $entry->title ?: 'Senza titolo',
            'slug'         => 'bozza-' . Str::random(8),
            'content'      => trim($entry->content),
            'visibility'   => 'draft',
            'published_at' => $entry->entry_date,
        ]);

        $post->entries()->attach($entry->id);

        $seq = 0;
        foreach ($entry->attachments as $att) {
            Attachment::create([
                'attachable_type' => Post::class,
                'attachable_id'   => $post->id,
                'media_id'        => $att->media_id,
                'sequence'        => $seq++,
            ]);
        }

        return $this->redirectRoute('blog.edit', ['post' => $post->id], navigate: true);
    }

    /**
     * Elimina la voce di diario (soft delete) e torna al diario. I file immagine
     * si rimuovono solo se non sono usati altrove (es. un post composto da questa
     * voce condivide gli stessi media): cosi' non si rompono i post.
     */
    public function deleteEntry()
    {
        $this->entry->load('attachments.media');

        foreach ($this->entry->attachments as $att) {
            $media = $att->media;
            $att->delete();
            if ($media && $media->attachments()->count() === 0) {
                app(MediaService::class)->delete($media);
            }
        }

        $this->entry->delete();

        return $this->redirectRoute('diario', navigate: true);
    }

    public function render()
    {
        return view('livewire.diario-show')
            ->layout('layouts.app', ['title' => $this->entry->entry_date->isoFormat('D MMMM YYYY')]);
    }
}
