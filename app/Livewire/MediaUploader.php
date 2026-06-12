<?php

namespace App\Livewire;

use App\Models\Attachment;
use App\Models\Media;
use App\Services\AttachmentService;
use App\Services\MediaService;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Livewire\WithFileUploads;

class MediaUploader extends Component
{
    use WithFileUploads;

    public string $entityType;
    public int    $entityId;

    public $uploads       = [];
    public ?int $lightbox = null;
    public array $captions = [];

    public bool $showLibrary  = false;
    public string $libSearch  = '';

    public function mount(string $entityType, int $entityId): void
    {
        $this->entityType = $entityType;
        $this->entityId   = $entityId;
        $this->loadCaptions();
    }

    public function updatedUploads(): void
    {
        $mediaService      = app(MediaService::class);
        $attachmentService = app(AttachmentService::class);
        $entity            = $this->resolveEntity();

        foreach ((array) $this->uploads as $file) {
            $media = $mediaService->store($file);
            $attachmentService->attach($media, $entity);
        }

        $this->uploads = [];
        $this->loadCaptions();
    }

    public function saveCaption(int $attachmentId): void
    {
        Attachment::where('id', $attachmentId)->update([
            'caption' => $this->captions[$attachmentId] ?? null,
        ]);
    }

    public function deleteAttachment(int $attachmentId): void
    {
        $attachment = Attachment::with('media')->find($attachmentId);
        if (!$attachment) return;

        $media = $attachment->media;
        $attachment->delete();

        if ($media && $media->attachments()->count() === 0) {
            app(MediaService::class)->delete($media);
        }

        if ($this->lightbox === $attachmentId) $this->lightbox = null;
        $this->loadCaptions();
    }

    public function reorder(array $orderedIds): void
    {
        app(AttachmentService::class)->reorder($orderedIds);
    }

    public function openLightbox(int $attachmentId): void
    {
        $this->lightbox = $attachmentId;
    }

    public function closeLightbox(): void
    {
        $this->lightbox = null;
    }

    public function toggleLibrary(): void
    {
        $this->showLibrary = !$this->showLibrary;
        $this->libSearch   = '';
    }

    public function attachFromLibrary(int $mediaId): void
    {
        $media  = Media::find($mediaId);
        $entity = $this->resolveEntity();
        if (!$media || !$entity) return;

        $exists = Attachment::where('media_id', $mediaId)
            ->where('attachable_type', $this->entityType)
            ->where('attachable_id', $this->entityId)
            ->exists();

        if (!$exists) {
            app(AttachmentService::class)->attach($media, $entity);
        }

        $this->loadCaptions();
    }

    public function render()
    {
        $attachments = Attachment::with('media')
            ->where('attachable_type', $this->entityType)
            ->where('attachable_id', $this->entityId)
            ->whereHas('media', fn($q) => $q->where('mime_type', 'like', 'image/%'))
            ->orderBy('sequence')
            ->get();

        $lightboxAttachment = $this->lightbox
            ? $attachments->firstWhere('id', $this->lightbox)
            : null;

        // ID già allegati a questa entità (per marcarli nella libreria)
        $attachedMediaIds = $attachments->pluck('media_id')->toArray();

        $libraryImages = $this->showLibrary
            ? Media::where('mime_type', 'like', 'image/%')
                ->when($this->libSearch, fn($q) => $q->where('original_filename', 'like', '%'.$this->libSearch.'%'))
                ->orderByDesc('id')
                ->get()
            : collect();

        return view('livewire.media-uploader',
            compact('attachments', 'lightboxAttachment', 'libraryImages', 'attachedMediaIds'));
    }

    private function resolveEntity(): Model
    {
        $class = $this->entityType;
        return $class::findOrFail($this->entityId);
    }

    private function loadCaptions(): void
    {
        $this->captions = Attachment::where('attachable_type', $this->entityType)
            ->where('attachable_id', $this->entityId)
            ->whereHas('media', fn($q) => $q->where('mime_type', 'like', 'image/%'))
            ->pluck('caption', 'id')
            ->toArray();
    }
}
