<?php

namespace App\Livewire;

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

        $post = Post::where('user_id', Auth::id())->findOrFail($postId);
        $data = ['visibility' => $visibility];
        if ($visibility === 'public' && !$post->published_at) {
            $data['published_at'] = now();
        }
        $post->update($data);
    }

    public function delete(int $postId): void
    {
        $post = Post::where('user_id', Auth::id())->with('attachments.media')->findOrFail($postId);

        // Rimuove le foto del post. Cancella il file fisico SOLO se nessun altro
        // attachment lo referenzia (il media puo' essere condiviso con una voce di diario).
        $post->attachments->each(function ($att) {
            $media = $att->media;
            $att->delete();
            if ($media && $media->attachments()->count() === 0) {
                app(MediaService::class)->delete($media);
            }
        });

        $post->delete();
        $this->confirmDeleteId = null;
    }

    public function render()
    {
        $posts = Post::where('user_id', Auth::id())
            ->with('attachments.media')
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
