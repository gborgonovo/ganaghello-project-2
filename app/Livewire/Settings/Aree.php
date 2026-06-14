<?php

namespace App\Livewire\Settings;

use App\Models\Area;
use Livewire\Component;

class Aree extends Component
{
    // Crea
    public string $newName     = '';
    public string $newColor    = '#5C6B4F';
    public ?int   $newParentId = null;

    // Edit inline
    public ?int   $editingId   = null;
    public string $editName    = '';
    public string $editColor   = '';
    public ?int   $editParentId = null;

    // Delete
    public ?int   $confirmDeleteId = null;
    public ?string $deleteError    = null;

    public function create(): void
    {
        $this->validate([
            'newName'  => 'required|string|max:255',
            'newColor' => 'nullable|string|max:7',
        ]);

        Area::create([
            'name'           => trim($this->newName),
            'color'          => $this->newColor,
            'parent_area_id' => $this->newParentId ?: null,
        ]);

        $this->newName     = '';
        $this->newColor    = '#5C6B4F';
        $this->newParentId = null;
        $this->dispatch('toast', message: 'Area creata.');
    }

    public function startEdit(int $id): void
    {
        $area             = Area::findOrFail($id);
        $this->editingId  = $id;
        $this->editName   = $area->name;
        $this->editColor  = $area->color ?? '#5C6B4F';
        $this->editParentId = $area->parent_area_id;
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editName'  => 'required|string|max:255',
            'editColor' => 'nullable|string|max:7',
        ]);

        Area::where('id', $this->editingId)->update([
            'name'           => trim($this->editName),
            'color'          => $this->editColor,
            'parent_area_id' => $this->editParentId ?: null,
        ]);

        $this->editingId = null;
        $this->dispatch('toast', message: 'Area aggiornata.');
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmDeleteId = $id;
        $this->deleteError     = null;
    }

    public function delete(int $id): void
    {
        $area = Area::withCount(['children', 'tasks', 'notes', 'entries', 'inspirations', 'posts'])->findOrFail($id);

        if ($area->children_count > 0) {
            $this->deleteError = 'Ha sotto-aree: rimuovile prima.';
            return;
        }

        $inUse = $area->tasks_count + $area->notes_count + $area->entries_count + $area->inspirations_count + $area->posts_count;
        if ($inUse > 0) {
            $this->deleteError = "Usata da {$inUse} elementi: riassegna prima.";
            return;
        }

        $area->delete();
        $this->confirmDeleteId = null;
        $this->deleteError     = null;
        $this->dispatch('toast', message: 'Area eliminata.');
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
        $this->deleteError     = null;
    }

    public function render()
    {
        $aree = Area::with('parent')->withCount(['children', 'tasks', 'notes', 'entries', 'inspirations', 'posts'])
            ->orderBy('parent_area_id')
            ->orderBy('name')
            ->get();

        $parents = Area::whereNull('parent_area_id')->orderBy('name')->get();

        return view('livewire.settings.aree', compact('aree', 'parents'))
            ->layout('layouts.app', ['title' => 'Aree']);
    }
}
