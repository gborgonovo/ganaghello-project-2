<?php

namespace App\Livewire;

use App\Models\Area;
use App\Models\Attachment;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Services\MediaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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
            $draft = Post::create([
                'user_id'    => Auth::id(),
                'title'      => 'Senza titolo',
                'slug'       => 'bozza-' . Str::random(8),
                'content'    => '',
                'visibility' => 'draft',
            ]);
            $this->redirectRoute('blog.edit', ['post' => $draft->id], navigate: true);
            return;
        }

        abort_unless($post->user_id === Auth::id(), 403);

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
            $this->slug = $this->uniqueSlug($this->title);
        }
    }

    public function save(): void
    {
        $this->validate();

        $this->slug = $this->uniqueSlug($this->slug ?: $this->title);

        $publishedAt = $this->post->published_at;
        if ($this->visibility === 'public' && !$publishedAt) {
            $publishedAt = $this->publishedAt ? \Carbon\Carbon::parse($this->publishedAt) : now();
        } elseif ($this->publishedAt) {
            $publishedAt = \Carbon\Carbon::parse($this->publishedAt);
        }

        $this->post->update([
            'title'        => trim($this->title),
            'slug'         => $this->slug,
            'excerpt'      => trim($this->excerpt) ?: null,
            'content'      => trim($this->content),
            'visibility'   => $this->visibility,
            'area_id'      => $this->areaId ?: null,
            'published_at' => $publishedAt,
        ]);

        $this->saved = true;
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
        $name = trim(strtolower($this->newTag));
        if ($name === '') return;

        $tag = Tag::firstOrCreate(['name' => $name]);
        $this->post->tags()->syncWithoutDetaching([$tag->id]);
        $this->newTag = '';
        $this->post->load('tags');
    }

    public function removeTag(int $tagId): void
    {
        $this->post->tags()->detach($tagId);
        $this->post->load('tags');
    }

    public function toggleLibrary(): void
    {
        $this->showLibrary = !$this->showLibrary;
        $this->libSearch   = '';
    }

    private function uniqueSlug(string $from): string
    {
        $base = Str::slug($from) ?: 'post';
        $slug = $base;
        $i    = 2;
        while (Post::where('slug', $slug)->where('id', '!=', $this->post->id)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
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
            ->layout('layouts.app', ['title' => $this->title ?: 'Nuovo post']);
    }
}
