<?php

namespace App\Livewire\Settings;

use App\Models\Tag;
use Livewire\Component;

class TagSettings extends Component
{
    public string $newName  = '';
    public string $newColor = '#5C6B4F';

    public ?int   $editingId  = null;
    public string $editName   = '';
    public string $editColor  = '';

    public ?int $confirmDeleteId = null;

    public function create(): void
    {
        $this->validate([
            'newName'  => 'required|string|max:100|unique:tags,name',
            'newColor' => 'nullable|string|max:7',
        ]);

        Tag::create([
            'name'  => trim($this->newName),
            'color' => $this->newColor,
        ]);

        $this->newName  = '';
        $this->newColor = '#5C6B4F';
        $this->dispatch('toast', message: 'Tag creato.');
    }

    public function startEdit(int $id): void
    {
        $tag             = Tag::findOrFail($id);
        $this->editingId = $id;
        $this->editName  = $tag->name;
        $this->editColor = $tag->color ?? '#5C6B4F';
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editName'  => 'required|string|max:100',
            'editColor' => 'nullable|string|max:7',
        ]);

        Tag::where('id', $this->editingId)->update([
            'name'  => trim($this->editName),
            'color' => $this->editColor,
        ]);

        $this->editingId = null;
        $this->dispatch('toast', message: 'Tag aggiornato.');
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
    }

    public function delete(int $id): void
    {
        $tag = Tag::findOrFail($id);
        // Rimuove automaticamente le associazioni via cascade sul DB (taggables)
        $tag->delete();
        $this->confirmDeleteId = null;
        $this->dispatch('toast', message: 'Tag eliminato.');
    }

    public function render()
    {
        $tags = Tag::withCount(['tasks', 'notes', 'entries', 'inspirations', 'posts'])
            ->orderBy('name')
            ->get();

        return view('livewire.settings.tag', compact('tags'))
            ->layout('layouts.app', ['title' => 'Tag']);
    }
}
