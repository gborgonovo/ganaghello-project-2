<?php

namespace App\Livewire;

use App\Models\Attachment;
use App\Models\Inspiration;
use App\Models\Media;
use App\Models\Stage;
use App\Models\Task;
use App\Services\AttachmentService;
use App\Services\MediaService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class InspirationShow extends Component
{
    public Inspiration $inspiration;

    public bool   $editing       = false;
    public string $editTitle     = '';
    public string $editDesc      = '';
    public string $editUrl       = '';
    public string $editImageUrl  = '';
    public ?int   $editAreaId    = null;

    public ?int $convertedTaskId = null;

    public function mount(Inspiration $inspiration): void
    {
        $this->inspiration = $inspiration;
    }

    public function startEdit(): void
    {
        $this->editTitle    = $this->inspiration->title ?? '';
        $this->editDesc     = $this->inspiration->description ?? '';
        $this->editUrl      = $this->inspiration->url ?? '';
        $this->editImageUrl = $this->inspiration->cover ?? '';
        $this->editAreaId   = $this->inspiration->area_id;
        $this->editing      = true;
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editTitle'    => 'nullable|string|max:200',
            'editDesc'     => 'nullable|string',
            'editUrl'      => 'nullable|url|max:500',
            'editImageUrl' => 'nullable|url|max:1000',
        ]);

        $oldCover = $this->inspiration->cover;

        $this->inspiration->update([
            'title'       => $this->editTitle ?: null,
            'description' => $this->editDesc ?: null,
            'url'         => $this->editUrl ?: null,
            'cover'       => $this->editImageUrl ?: null,
            'area_id'     => $this->editAreaId,
        ]);

        if ($this->editImageUrl && $this->editImageUrl !== $oldCover && !$this->inspiration->attachments()->exists()) {
            \App\Jobs\LocalizeInspirationCover::dispatch($this->inspiration->id);
        }

        $this->inspiration->refresh();
        $this->editing = false;
    }

    public function cancelEdit(): void
    {
        $this->editing = false;
    }

    public function delete(): void
    {
        $insp = $this->inspiration->load('attachments.media');

        foreach ($insp->attachments as $att) {
            $media = $att->media;
            $att->delete();
            if ($media && $media->attachments()->count() === 0) {
                app(MediaService::class)->delete($media);
            }
        }

        $insp->delete();

        $this->redirect(route('moodboard'), navigate: true);
    }

    public function convertToTask(): void
    {
        $insp  = $this->inspiration->load('attachments.media');
        $stage = Stage::where('code', 'idea')->first();
        if (!$stage) return;

        $task = Task::create([
            'user_id'     => Auth::id(),
            'title'       => $insp->title ?? 'Idea da moodboard',
            'description' => $insp->description,
            'area_id'     => $insp->area_id,
            'stage_id'    => $stage->id,
        ]);

        foreach ($insp->attachments as $att) {
            app(AttachmentService::class)->attach($att->media, $task);
        }

        $this->convertedTaskId = $task->id;
    }

    public function render()
    {
        $this->inspiration->load(['area', 'attachments.media']);

        $aree = \App\Models\Area::orderBy('name')->get();

        $coverMedia = $this->inspiration->attachments->first()?->media;
        $coverUrl   = $coverMedia
            ? route('media.serve', [$coverMedia, 'medium'])
            : $this->inspiration->cover;

        return view('livewire.inspiration-show', compact('coverMedia', 'coverUrl', 'aree'))
            ->layout('layouts.app', ['title' => $this->inspiration->title ?? 'Ispirazione']);
    }
}
