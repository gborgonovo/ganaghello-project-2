<?php

namespace App\Livewire;

use App\Models\Area;
use App\Models\Note;
use Livewire\Component;

class NoteShow extends Component
{
    public Note $note;

    public bool   $showModal    = false;
    public string $modalTitle   = '';
    public string $modalContent = '';
    public ?int   $modalAreaId  = null;

    public function mount(Note $note): void
    {
        $this->note = $note->load('area');
    }

    public function openEdit(): void
    {
        $this->modalTitle   = $this->note->title ?? '';
        $this->modalContent = $this->note->content;
        $this->modalAreaId  = $this->note->area_id;
        $this->showModal    = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetValidation();
    }

    public function saveModal(): void
    {
        $this->validate(['modalContent' => 'required|string']);

        $this->note->update([
            'title'   => $this->modalTitle ?: null,
            'content' => trim($this->modalContent),
            'area_id' => $this->modalAreaId ?: null,
        ]);

        $this->note->refresh()->load('area');
        $this->showModal = false;
        $this->dispatch('toast', message: 'Nota salvata.');
    }

    public function render()
    {
        $aree = Area::orderBy('name')->get();

        return view('livewire.note-show', compact('aree'))
            ->layout('layouts.app', ['title' => $this->note->title ?? 'Nota']);
    }
}
