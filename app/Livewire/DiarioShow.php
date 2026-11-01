<?php

namespace App\Livewire;

use App\Actions\PostActions;
use App\Actions\VoceActions;
use App\Models\Entry;
use App\Services\MediaService;
use Illuminate\Support\Facades\Auth;
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
        $post = app(PostActions::class)->componi(Auth::user(), [$this->entry->id]);

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

        app(VoceActions::class)->elimina(Auth::user(), $this->entry);

        return $this->redirectRoute('diario', navigate: true);
    }

    public function render()
    {
        return view('livewire.diario-show')
            ->layout('layouts.app', ['title' => $this->entry->entry_date->isoFormat('D MMMM YYYY')]);
    }
}
