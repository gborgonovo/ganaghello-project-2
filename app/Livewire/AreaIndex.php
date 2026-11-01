<?php

namespace App\Livewire;

use App\Actions\AreaActions;
use App\Exceptions\RegolaDominio;
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

        app(AreaActions::class)->modifica(Auth::user(), Area::findOrFail($this->editingId), [
            'name'           => $this->editName,
            'color'          => $this->editColor,
            'status'         => $this->editStatus,
            'description'    => $this->editDesc,
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

        app(AreaActions::class)->crea(Auth::user(), [
            'name'           => $this->newName,
            'color'          => $this->newColor,
            'description'    => $this->newDesc,
            'parent_area_id' => $this->newParentId ?: null,
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
        try {
            app(AreaActions::class)->elimina(Auth::user(), Area::findOrFail($id));
        } catch (RegolaDominio $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
            return;
        }
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
