<?php

namespace App\Livewire;

use App\Actions\PostActions;
use App\Models\Post;
use App\Services\MediaService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BlogIndex extends Component
{
    public string  $search          = '';
    public string  $filterVisibility = '';
    public ?int    $confirmDeleteId  = null;

    public function setVisibility(int $postId, string $visibility): void
    {
        if (!in_array($visibility, ['private', 'draft', 'public'])) return;

        app(PostActions::class)->modifica(Auth::user(), Post::findOrFail($postId), ['visibility' => $visibility]);
    }

    public function delete(int $postId): void
    {
        $post = Post::with('attachments.media')->findOrFail($postId);

        // Rimuove le foto del post. Cancella il file fisico SOLO se nessun altro
        // attachment lo referenzia (il media puo' essere condiviso con una voce di diario).
        $post->attachments->each(function ($att) {
            $media = $att->media;
            $att->delete();
            if ($media && $media->attachments()->count() === 0) {
                app(MediaService::class)->delete($media);
            }
        });

        app(PostActions::class)->elimina(Auth::user(), $post);
        $this->confirmDeleteId = null;
    }

    public function render()
    {
        $posts = Post::with('attachments.media')
            ->when($this->filterVisibility, fn ($q) => $q->where('visibility', $this->filterVisibility))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                  ->orWhere('excerpt', 'like', '%'.$this->search.'%')
                  ->orWhere('content', 'like', '%'.$this->search.'%');
            }))
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.blog-index', compact('posts'))
            ->layout('layouts.app', ['title' => 'Blog']);
    }
}
