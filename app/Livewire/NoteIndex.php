<?php

namespace App\Livewire;

use App\Models\Area;
use App\Models\Note;
use App\Models\Stage;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NoteIndex extends Component
{
    // Quick capture
    public string $newTitle   = '';
    public string $newContent = '';
    public ?int   $newAreaId  = null;

    // Ricerca e filtro
    public string $search       = '';
    public ?int   $filterAreaId = null;

    // Edit
    public ?int   $editingId      = null;
    public string $editTitle      = '';
    public string $editContent    = '';
    public ?int   $editAreaId     = null;

    // Delete
    public ?int $confirmDeleteId = null;

    // Conversione a task
    public ?int $convertedTaskId = null;

    public function create(): void
    {
        $this->validate([
            'newTitle'   => 'required|string|max:255',
            'newContent' => 'required|string',
        ]);

        Note::create([
            'user_id' => Auth::id(),
            'area_id' => $this->newAreaId,
            'title'   => trim($this->newTitle),
            'content' => trim($this->newContent),
        ]);

        $this->newTitle   = '';
        $this->newContent = '';
        $this->newAreaId  = null;
    }

    public function startEdit(int $id): void
    {
        $note = Note::findOrFail($id);
        $this->editingId   = $id;
        $this->editTitle   = $note->title ?? '';
        $this->editContent = $note->content;
        $this->editAreaId  = $note->area_id;
    }

    public function saveEdit(): void
    {
        $this->validate(['editContent' => 'required|string']);

        Note::where('id', $this->editingId)->update([
            'title'   => $this->editTitle ?: null,
            'content' => $this->editContent,
            'area_id' => $this->editAreaId,
        ]);

        $this->editingId = null;
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
    }

    public function delete(int $id): void
    {
        Note::find($id)?->delete();
        $this->confirmDeleteId = null;
    }

    public function convertToTask(int $id): void
    {
        $note  = Note::findOrFail($id);
        $stage = Stage::where('code', 'idea')->first();
        if (!$stage) return;

        $task = Task::create([
            'user_id'     => Auth::id(),
            'title'       => $note->title ?? str($note->content)->limit(60)->toString(),
            'description' => $note->content,
            'area_id'     => $note->area_id,
            'stage_id'    => $stage->id,
        ]);

        $this->convertedTaskId = $task->id;
    }

    public function render()
    {
        $aree = Area::orderBy('name')->get();

        $notes = Note::with('area')
            ->when($this->filterAreaId, fn($q) => $q->where('area_id', $this->filterAreaId))
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                  ->orWhere('content', 'like', '%'.$this->search.'%');
            }))
            ->orderByDesc('updated_at')
            ->get();

        return view('livewire.note-index', compact('aree', 'notes'))
            ->layout('layouts.app', ['title' => 'Note']);
    }
}
