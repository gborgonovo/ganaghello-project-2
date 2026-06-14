<?php

namespace App\Livewire;

use App\Jobs\LocalizeInspirationCover;
use App\Models\Area;
use App\Models\Attachment;
use App\Models\Inspiration;
use App\Models\Media;
use App\Services\AttachmentService;
use App\Services\MediaService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class MoodboardIndex extends Component
{
    use WithFileUploads;

    // Form nuova ispirazione
    public string  $newTitle        = '';
    public string  $newDescription  = '';
    public string  $newUrl          = '';
    public string  $newImageUrl     = '';
    public ?int    $newAreaId       = null;
    public         $newImage           = null;
    public bool    $showForm           = false;
    public bool    $showImageLibrary   = false;
    public string  $imageLibSearch     = '';
    public ?int    $newImageMediaId    = null;
    public ?string $newImagePreviewUrl = null;

    // Filtro
    public ?int $filterAreaId = null;

    // Delete
    public ?int $confirmDeleteId = null;

    public function toggleForm(): void
    {
        $this->showForm = !$this->showForm;
        if (!$this->showForm) $this->resetForm();
    }

    public function toggleImageLibrary(): void
    {
        $this->showImageLibrary = !$this->showImageLibrary;
        $this->imageLibSearch   = '';
    }

    public function selectImageFromLibrary(int $mediaId): void
    {
        $media = Media::find($mediaId);
        if (!$media) return;
        $this->newImageMediaId    = $mediaId;
        $this->newImagePreviewUrl = route('media.serve', [$media, 'medium']);
        $this->newImage           = null;
        $this->showImageLibrary   = false;
    }

    public function create(): void
    {
        $this->validate([
            'newTitle'       => 'nullable|string|max:200',
            'newDescription' => 'nullable|string',
            'newUrl'         => 'nullable|url|max:500',
            'newImageUrl'    => 'nullable|url|max:1000',
            'newImage'       => 'nullable|image|max:10240',
        ]);

        if (!$this->newTitle && !$this->newDescription && !$this->newUrl && !$this->newImageUrl && !$this->newImage && !$this->newImageMediaId) {
            $this->dispatch('toast', message: 'Aggiungi almeno un contenuto.', type: 'error');
            return;
        }

        $inspiration = Inspiration::create([
            'user_id'     => Auth::id(),
            'area_id'     => $this->newAreaId,
            'title'       => $this->newTitle ?: null,
            'description' => $this->newDescription ?: null,
            'url'         => $this->newUrl ?: null,
            'cover'       => $this->newImageUrl ?: null,
        ]);

        if ($this->newImage) {
            $media = app(MediaService::class)->store($this->newImage, 'inspiration');
            app(AttachmentService::class)->attach($media, $inspiration);
        } elseif ($this->newImageUrl) {
            LocalizeInspirationCover::dispatch($inspiration->id);
        } elseif ($this->newImageMediaId) {
            Attachment::create([
                'attachable_type' => Inspiration::class,
                'attachable_id'   => $inspiration->id,
                'media_id'        => $this->newImageMediaId,
                'sequence'        => 1,
            ]);
        }

        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('toast', message: 'Ispirazione salvata.');
    }

    public function updatedNewUrl(): void
    {
        // Auto-fill title dal dominio se vuoto
        if ($this->newUrl && !$this->newTitle) {
            $host = parse_url($this->newUrl, PHP_URL_HOST) ?? '';
            $this->newTitle = $host ? str($host)->after('www.')->title()->toString() : '';
        }
    }

    public function delete(int $id): void
    {
        $insp = Inspiration::with('attachments.media')->find($id);
        if (!$insp) return;

        foreach ($insp->attachments as $att) {
            $media = $att->media;
            $att->delete();
            if ($media && $media->attachments()->count() === 0) {
                app(MediaService::class)->delete($media);
            }
        }

        $insp->delete();
        $this->confirmDeleteId = null;
        $this->dispatch('toast', message: 'Ispirazione eliminata.');
    }

    public function render()
    {
        $aree = Area::orderBy('name')->get();

        $inspirations = Inspiration::with(['area', 'attachments.media'])
            ->when($this->filterAreaId, fn($q) => $q->where('area_id', $this->filterAreaId))
            ->orderByDesc('id')
            ->get();

        $imageLibraryImages = $this->showImageLibrary
            ? Media::where('mime_type', 'like', 'image/%')
                ->when($this->imageLibSearch, fn ($q) => $q->where('original_filename', 'like', '%'.$this->imageLibSearch.'%'))
                ->orderByDesc('created_at')->limit(48)->get()
            : collect();

        return view('livewire.moodboard-index', compact('aree', 'inspirations', 'imageLibraryImages'))
            ->layout('layouts.app', ['title' => 'Moodboard']);
    }

    private function resetForm(): void
    {
        $this->newTitle           = '';
        $this->newDescription     = '';
        $this->newUrl             = '';
        $this->newImageUrl        = '';
        $this->newAreaId          = null;
        $this->newImage           = null;
        $this->newImageMediaId    = null;
        $this->newImagePreviewUrl = null;
        $this->showImageLibrary   = false;
        $this->imageLibSearch     = '';
    }
}
