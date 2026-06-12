<?php

namespace App\Livewire;

use App\Models\Area;
use App\Models\Attachment;
use App\Models\Media;
use App\Models\Task;
use App\Services\AttachmentService;
use App\Services\MediaService;
use Livewire\Component;
use Livewire\WithFileUploads;

class MediaLibrary extends Component
{
    use WithFileUploads;

    public string  $tab           = 'images';
    public ?int    $areaId        = null;
    public ?int    $lightbox      = null;
    public ?string $filterContext = '';

    public $imgUploads = [];
    public $docUpload  = null;

    // Collegamento immagine a task
    public ?int $linkingMediaId = null;
    public ?int $linkTaskId     = null;

    public function switchTab(string $tab): void
    {
        $this->tab      = $tab;
        $this->lightbox = null;
        $this->cancelLink();
    }

    public function updatedImgUploads(): void
    {
        $service = app(MediaService::class);
        foreach ((array) $this->imgUploads as $file) {
            $service->store($file);
        }
        $this->imgUploads = [];
    }

    public function updatedDocUpload(): void
    {
        if (!$this->docUpload) return;
        app(MediaService::class)->store($this->docUpload);
        $this->docUpload = null;
    }

    public function openLightbox(int $mediaId): void
    {
        $this->lightbox = $mediaId;
    }

    public function closeLightbox(): void
    {
        $this->lightbox = null;
    }

    public function toggleContext(int $mediaId): void
    {
        $media = Media::find($mediaId);
        if (!$media) return;
        $media->context = $media->context === 'inspiration' ? null : 'inspiration';
        $media->save();
    }

    public function deleteMedia(int $mediaId): void
    {
        $media = Media::find($mediaId);
        if (!$media) return;

        if ($this->lightbox === $mediaId) $this->lightbox = null;
        if ($this->linkingMediaId === $mediaId) $this->cancelLink();

        app(MediaService::class)->delete($media);
    }

    public function startLink(int $mediaId): void
    {
        $this->linkingMediaId = $mediaId;
        $this->linkTaskId     = null;
    }

    public function cancelLink(): void
    {
        $this->linkingMediaId = null;
        $this->linkTaskId     = null;
    }

    public function confirmLink(): void
    {
        if (!$this->linkingMediaId || !$this->linkTaskId) return;

        $media = Media::find($this->linkingMediaId);
        $task  = Task::find($this->linkTaskId);
        if (!$media || !$task) return;

        // Evita duplicati
        $exists = Attachment::where('media_id', $media->id)
            ->where('attachable_type', Task::class)
            ->where('attachable_id', $task->id)
            ->exists();

        if (!$exists) {
            app(AttachmentService::class)->attach($media, $task);
        }

        $this->cancelLink();
    }

    public function render()
    {
        $aree = Area::orderBy('name')->get();

        $contextFilter = fn($q) => match($this->filterContext) {
            ''    => $q->whereNull('context'),
            null  => $q,
            default => $q->where('context', $this->filterContext),
        };

        $images = Media::with(['attachments' => fn($q) => $q->with('attachable')])
            ->where('mime_type', 'like', 'image/%')
            ->tap($contextFilter)
            ->when($this->areaId, fn($q) => $q->whereHas('attachments', fn($a) =>
                $a->where('attachable_type', Task::class)
                  ->whereHas('attachable', fn($t) => $t->where('area_id', $this->areaId))
            ))
            ->orderByDesc('id')
            ->get();

        $docs = Media::with(['attachments' => fn($q) => $q->with('attachable')])
            ->where('mime_type', 'not like', 'image/%')
            ->tap($contextFilter)
            ->when($this->areaId, fn($q) => $q->whereHas('attachments', fn($a) =>
                $a->where('attachable_type', Task::class)
                  ->whereHas('attachable', fn($t) => $t->where('area_id', $this->areaId))
            ))
            ->orderByDesc('id')
            ->get();

        $lightboxMedia = $this->lightbox ? $images->firstWhere('id', $this->lightbox) : null;

        $tasks = $this->linkingMediaId
            ? Task::with('area')->whereNull('deleted_at')->orderBy('title')->get()
            : collect();

        return view('livewire.media-library', compact('aree', 'images', 'docs', 'lightboxMedia', 'tasks'))
            ->title('Libreria media');
    }
}
