<?php

namespace App\Livewire;

use App\Models\Attachment;
use App\Services\AttachmentService;
use App\Services\MediaService;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Livewire\WithFileUploads;

class DocUploader extends Component
{
    use WithFileUploads;

    public string $entityType;
    public int    $entityId;

    public $docUpload = null;

    public function mount(string $entityType, int $entityId): void
    {
        $this->entityType = $entityType;
        $this->entityId   = $entityId;
    }

    public function updatedDocUpload(): void
    {
        if (!$this->docUpload) return;

        $this->validate(['docUpload' => 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:15360']);

        $entity = $this->resolveEntity();
        $media  = app(MediaService::class)->store($this->docUpload);
        app(AttachmentService::class)->attach($media, $entity);
        $this->docUpload = null;
    }

    public function deleteDoc(int $attachmentId): void
    {
        $attachment = Attachment::with('media')->find($attachmentId);
        if (!$attachment) return;

        $media = $attachment->media;
        $attachment->delete();

        if ($media && $media->attachments()->count() === 0) {
            app(MediaService::class)->delete($media);
        }
    }

    public function render()
    {
        $docs = Attachment::with('media')
            ->where('attachable_type', $this->entityType)
            ->where('attachable_id', $this->entityId)
            ->whereHas('media', fn($q) => $q->where('mime_type', 'not like', 'image/%'))
            ->orderBy('sequence')
            ->get();

        return view('livewire.doc-uploader', compact('docs'));
    }

    private function resolveEntity(): Model
    {
        $class = $this->entityType;
        return $class::findOrFail($this->entityId);
    }
}
