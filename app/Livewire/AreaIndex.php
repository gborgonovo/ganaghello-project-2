<?php

namespace App\Livewire;

use App\Models\Area;
use App\Models\Stage;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AreaIndex extends Component
{
    // Edit inline
    public ?int   $editingId   = null;
    public string $editName    = '';
    public string $editColor   = '';
    public string $editStatus  = '';
    public string $editDesc    = '';
    public ?int   $editParentId = null;

    // Crea nuova area
    public bool   $showCreate  = false;
    public string $newName     = '';
    public string $newColor    = '#5C6B4F';
    public string $newDesc     = '';
    public ?int   $newParentId = null;

    // Conferma eliminazione
    public ?int  $confirmDeleteId = null;

    public function startEdit(int $id): void
    {
        $area               = Area::findOrFail($id);
        $this->editingId    = $id;
        $this->editName     = $area->name;
        $this->editColor    = $area->color ?? '';
        $this->editStatus   = $area->status ?? '';
        $this->editDesc     = $area->description ?? '';
        $this->editParentId = $area->parent_area_id;
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editName'  => 'required|string|max:100',
            'editColor' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        Area::where('id', $this->editingId)->update([
            'name'           => trim($this->editName),
            'color'          => $this->editColor ?: null,
            'status'         => $this->editStatus ?: null,
            'description'    => trim($this->editDesc) ?: null,
            'parent_area_id' => $this->editParentId ?: null,
        ]);

        $this->editingId = null;
        $this->dispatch('toast', message: 'Area aggiornata.');
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
    }

    public function createArea(): void
    {
        $this->validate([
            'newName'  => 'required|string|max:100',
            'newColor' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $max = Area::max('sequence') ?? 0;
        Area::create([
            'name'           => trim($this->newName),
            'color'          => $this->newColor ?: null,
            'description'    => trim($this->newDesc) ?: null,
            'parent_area_id' => $this->newParentId ?: null,
            'sequence'       => $max + 1,
        ]);

        $this->showCreate  = false;
        $this->newName     = '';
        $this->newColor    = '#5C6B4F';
        $this->newDesc     = '';
        $this->newParentId = null;
        $this->dispatch('toast', message: 'Area creata.');
    }

    public function deleteArea(int $id): void
    {
        $area = Area::withCount('tasks')->findOrFail($id);
        if ($area->tasks_count > 0) {
            $this->dispatch('toast', message: "Usata da {$area->tasks_count} task: riassegna prima.", type: 'error');
            return;
        }
        $area->delete();
        $this->confirmDeleteId = null;
        $this->dispatch('toast', message: 'Area eliminata.');
    }

    public function render()
    {
        $doneIds = Stage::whereIn('code', ['done', 'archiviato'])->pluck('id');

        $areas = Area::with(['children' => function ($q) use ($doneIds) {
            $q->withCount([
                'tasks',
                'tasks as open_tasks_count' => fn($q2) => $q2->whereNotIn('stage_id', $doneIds),
            ])->orderBy('sequence');
        }])
            ->whereNull('parent_area_id')
            ->withCount([
                'tasks',
                'tasks as open_tasks_count' => fn($q) => $q->whereNotIn('stage_id', $doneIds),
            ])
            ->orderBy('sequence')
            ->get();

        $allAreas = Area::orderBy('sequence')->get();

        return view('livewire.area-index', compact('areas', 'allAreas'))
            ->layout('layouts.app', ['title' => 'Aree']);
    }
}
