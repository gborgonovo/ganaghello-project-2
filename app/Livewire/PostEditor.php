<?php

namespace App\Livewire;

use App\Actions\PostActions;
use App\Actions\TagActions;
use App\Models\Area;
use App\Models\Attachment;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Services\MediaService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class PostEditor extends Component
{
    use WithFileUploads;

    public Post $post;

    public string  $title      = '';
    public string  $slug       = '';
    public string  $excerpt    = '';
    public string  $content    = '';
    public string  $visibility = 'draft';
    public ?int    $areaId     = null;
    public string  $publishedAt = '';

    // Set fotografico
    public $newPhotos = [];           // upload multiplo
    public bool   $showLibrary = false;
    public string $libSearch   = '';

    // Tag
    public string $newTag = '';

    public bool $saved = false;

    protected function rules(): array
    {
        return [
            'title'      => 'required|string|max:255',
            'slug'       => 'required|string|max:255',
            'excerpt'    => 'nullable|string|max:500',
            'content'    => 'nullable|string',
            'visibility' => 'required|in:private,draft,public',
            'areaId'     => 'nullable|exists:areas,id',
            'newPhotos.*'=> 'nullable|image|max:20480',
        ];
    }

    public function mount(?Post $post = null): void
    {
        // /blog/nuovo: crea una bozza e rimanda all'URL di modifica (no duplicati al refresh)
        if (!$post || !$post->exists) {
            $draft = app(PostActions::class)->crea(Auth::user());
            $this->redirectRoute('blog.edit', ['post' => $draft->id], navigate: true);
            return;
        }

        $this->post        = $post->load('attachments.media', 'tags');
        $this->title       = $post->title === 'Senza titolo' ? '' : $post->title;
        $this->slug        = $post->slug;
        $this->excerpt     = $post->excerpt ?? '';
        $this->content     = $post->content ?? '';
        $this->visibility  = $post->visibility;
        $this->areaId      = $post->area_id;
        $this->publishedAt = $post->published_at?->format('Y-m-d') ?? '';
    }

    public function updatedTitle(): void
    {
        // Auto-slug solo se lo slug e' ancora una bozza autogenerata
        if (str_starts_with($this->slug, 'bozza-') || $this->slug === '') {
            $this->slug = app(PostActions::class)->slugUnico($this->title, $this->post->id);
        }
    }

    public function save(): void
    {
        $this->validate();

        $this->post = app(PostActions::class)->modifica(Auth::user(), $this->post, [
            'title'        => $this->title,
            'slug'         => $this->slug ?: $this->title,
            'excerpt'      => $this->excerpt,
            'content'      => $this->content,
            'visibility'   => $this->visibility,
            'area_id'      => $this->areaId ?: null,
            'published_at' => $this->publishedAt ?: null,
        ])->load('attachments.media', 'tags');
        $this->slug = $this->post->slug;

        $this->saved = true;
        $this->dispatch('toast', message: 'Post salvato.');
    }

    // ===== SET FOTOGRAFICO =====

    public function updatedNewPhotos(): void
    {
        $this->validate(['newPhotos.*' => 'image|max:20480']);

        $nextSeq = (int) ($this->post->attachments()->max('sequence') ?? -1) + 1;

        foreach ($this->newPhotos as $photo) {
            $media = app(MediaService::class)->store($photo);
            Attachment::create([
                'attachable_type' => Post::class,
                'attachable_id'   => $this->post->id,
                'media_id'        => $media->id,
                'sequence'        => $nextSeq++,
            ]);
        }

        $this->newPhotos = [];
        $this->post->load('attachments.media');
    }

    public function addFromLibrary(int $mediaId): void
    {
        $nextSeq = (int) ($this->post->attachments()->max('sequence') ?? -1) + 1;
        Attachment::create([
            'attachable_type' => Post::class,
            'attachable_id'   => $this->post->id,
            'media_id'        => $mediaId,
            'sequence'        => $nextSeq,
        ]);
        $this->showLibrary = false;
        $this->post->load('attachments.media');
    }

    public function removePhoto(int $attachmentId): void
    {
        $att = $this->post->attachments()->find($attachmentId);
        if ($att) {
            $att->delete();
            $this->post->load('attachments.media');
        }
    }

    public function makeCover(int $attachmentId): void
    {
        $min = (int) $this->post->attachments()->min('sequence');
        $this->post->attachments()->where('id', $attachmentId)->update(['sequence' => $min - 1]);
        $this->post->load('attachments.media');
    }

    public function movePhoto(int $attachmentId, string $dir): void
    {
        $atts = $this->post->attachments()->orderBy('sequence')->orderBy('id')->get()->values();
        $idx  = $atts->search(fn ($a) => $a->id === $attachmentId);
        if ($idx === false) return;

        $swapIdx = $dir === 'up' ? $idx - 1 : $idx + 1;
        if ($swapIdx < 0 || $swapIdx >= $atts->count()) return;

        $a = $atts[$idx];
        $b = $atts[$swapIdx];
        [$a->sequence, $b->sequence] = [$b->sequence, $a->sequence];
        $a->save();
        $b->save();

        $this->post->load('attachments.media');
    }

    // ===== TAG =====

    public function addTag(): void
    {
        if (trim($this->newTag) === '') return;

        app(TagActions::class)->applica(Auth::user(), $this->post, [$this->newTag]);
        $this->newTag = '';
        $this->post->load('tags');
    }

    public function removeTag(int $tagId): void
    {
        app(TagActions::class)->applica(Auth::user(), $this->post, togli: [$tagId]);
        $this->post->load('tags');
    }

    public function toggleLibrary(): void
    {
        $this->showLibrary = !$this->showLibrary;
        $this->libSearch   = '';
    }

    public function render()
    {
        $aree = Area::orderBy('name')->get();

        $libraryImages = $this->showLibrary
            ? Media::where('mime_type', 'like', 'image/%')
                ->when($this->libSearch, fn ($q) => $q->where('original_filename', 'like', '%'.$this->libSearch.'%'))
                ->orderByDesc('created_at')->limit(48)->get()
            : collect();

        $photos = $this->post->attachments()->with('media')->orderBy('sequence')->orderBy('id')->get();

        $sourceEntries = $this->post->entries()->orderByDesc('entry_date')->get();

        return view('livewire.post-editor', compact('aree', 'libraryImages', 'photos', 'sourceEntries'))
            ->layout('layouts.focus', ['title' => $this->title ?: 'Nuovo post']);
    }
}
