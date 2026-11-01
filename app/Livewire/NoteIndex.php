<?php

namespace App\Livewire;

use App\Actions\NotaActions;
use App\Actions\TaskActions;
use App\Models\Area;
use App\Models\Note;
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

        app(NotaActions::class)->crea(Auth::user(), [
            'area_id' => $this->newAreaId,
            'title'   => $this->newTitle,
            'content' => $this->newContent,
        ]);

        $this->newTitle   = '';
        $this->newContent = '';
        $this->newAreaId  = null;
        $this->dispatch('toast', message: 'Nota creata.');
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

        app(NotaActions::class)->modifica(Auth::user(), Note::findOrFail($this->editingId), [
            'title'   => $this->editTitle ?: null,
            'content' => $this->editContent,
            'area_id' => $this->editAreaId,
        ]);

        $this->editingId = null;
        $this->dispatch('toast', message: 'Nota aggiornata.');
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
    }

    public function delete(int $id): void
    {
        if ($nota = Note::find($id)) {
            app(NotaActions::class)->elimina(Auth::user(), $nota);
        }
        $this->confirmDeleteId = null;
        $this->dispatch('toast', message: 'Nota eliminata.');
    }

    public function convertToTask(int $id): void
    {
        $note = Note::findOrFail($id);

        $task = app(TaskActions::class)->crea(Auth::user(), [
            'title'       => $note->title ?? str($note->content)->limit(60)->toString(),
            'description' => $note->content,
            'area_id'     => $note->area_id,
        ]);

        $this->convertedTaskId = $task->id;
        $this->dispatch('toast', message: 'Nota convertita in task.');
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
