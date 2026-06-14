<?php

namespace App\Livewire;

use App\Models\Area;
use App\Models\Attachment;
use App\Models\Entry;
use App\Models\Media;
use App\Models\Post;
use App\Services\MediaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class DiarioShow extends Component
{
    use WithFileUploads;

    public Entry $entry;

    public bool    $showModal        = false;
    public string  $modalTitle       = '';
    public string  $modalContent     = '';
    public string  $modalDate        = '';
    public string  $modalTime        = '';
    public ?int    $modalAreaId      = null;
    public         $modalCover            = null;
    public ?string $currentCoverUrl       = null;
    public bool    $removeCover           = false;
    public bool    $showLibrary           = false;
    public string  $libSearch             = '';
    public ?int    $modalCoverMediaId     = null;
    public ?string $selectedLibraryUrl    = null;

    public function mount(Entry $entry): void
    {
        $this->entry = $entry->load('area', 'attachments.media', 'posts');
    }

    protected function rules(): array
    {
        return [
            'modalTitle'   => 'required|string|max:255',
            'modalContent' => 'required|string',
            'modalDate'    => 'required|date',
            'modalTime'    => ['nullable', 'regex:/^\d{1,2}:\d{2}$/'],
            'modalCover'   => 'nullable|image|max:20480',
        ];
    }

    public function openEdit(): void
    {
        $this->modalTitle   = $this->entry->title ?? '';
        $this->modalContent = $this->entry->content;
        $this->modalDate    = $this->entry->entry_date->toDateString();
        $this->modalTime    = $this->entry->entry_time ? substr($this->entry->entry_time, 0, 5) : '';
        $this->modalAreaId  = $this->entry->area_id;

        $cover = $this->entry->attachments->first()?->media;
        $this->currentCoverUrl  = $cover ? route('media.serve', [$cover, 'medium']) : null;
        $this->removeCover      = false;
        $this->modalCover       = null;
        $this->modalCoverMediaId= null;
        $this->selectedLibraryUrl = null;

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal  = false;
        $this->modalCover = null;
        $this->resetValidation();
    }

    public function toggleLibrary(): void
    {
        $this->showLibrary = !$this->showLibrary;
        $this->libSearch   = '';
    }

    public function selectFromLibrary(int $mediaId): void
    {
        $media = Media::find($mediaId);
        if (!$media) return;
        $this->modalCoverMediaId  = $mediaId;
        $this->selectedLibraryUrl = route('media.serve', [$media, 'medium']);
        $this->modalCover         = null;
        $this->removeCover        = false;
        $this->showLibrary        = false;
    }

    public function saveModal(): void
    {
        $this->validate();

        $this->entry->update([
            'area_id'    => $this->modalAreaId ?: null,
            'title'      => trim($this->modalTitle),
            'content'    => trim($this->modalContent),
            'entry_date' => $this->modalDate,
            'entry_time' => $this->modalTime ?: null,
        ]);

        if ($this->removeCover || $this->modalCover || $this->modalCoverMediaId) {
            $this->entry->load('attachments.media');
            $this->entry->attachments->each(fn($att) => $att->delete());
        }

        if ($this->modalCover) {
            $media = app(MediaService::class)->store($this->modalCover);
            Attachment::create([
                'attachable_type' => Entry::class,
                'attachable_id'   => $this->entry->id,
                'media_id'        => $media->id,
                'sequence'        => 1,
            ]);
        }

        if ($this->modalCoverMediaId && !$this->removeCover) {
            Attachment::create([
                'attachable_type' => Entry::class,
                'attachable_id'   => $this->entry->id,
                'media_id'        => $this->modalCoverMediaId,
                'sequence'        => 1,
            ]);
        }

        $this->entry->refresh()->load('area', 'attachments.media');
        $this->showModal  = false;
        $this->modalCover = null;
        $this->dispatch('toast', message: 'Pagina aggiornata.');
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
        $aree = Area::orderBy('name')->get();

        $libraryImages = $this->showLibrary
            ? Media::where('mime_type', 'like', 'image/%')
                ->when($this->libSearch, fn($q) => $q->where('original_filename', 'like', '%'.$this->libSearch.'%'))
                ->orderByDesc('created_at')->limit(48)->get()
            : collect();

        return view('livewire.diario-show', compact('aree', 'libraryImages'))
            ->layout('layouts.app', ['title' => $this->entry->entry_date->isoFormat('D MMMM YYYY')]);
    }
}
