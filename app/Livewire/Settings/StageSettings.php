<?php

namespace App\Livewire\Settings;

use App\Models\Stage;
use Livewire\Component;

class StageSettings extends Component
{
    // Crea
    public string $newCode      = '';
    public string $newLabel     = '';
    public string $newBgColor   = '#E0DDD2';
    public string $newTextColor = '#2C322A';

    // Edit inline
    public ?int   $editingId      = null;
    public string $editLabel      = '';
    public string $editBgColor    = '';
    public string $editTextColor  = '';

    // Delete
    public ?int    $confirmDeleteId = null;
    public ?string $deleteError     = null;

    public function create(): void
    {
        $this->validate([
            'newCode'      => 'required|string|max:50|unique:stages,code',
            'newLabel'     => 'required|string|max:255',
            'newBgColor'   => 'required|string|max:7',
            'newTextColor' => 'required|string|max:7',
        ]);

        $maxSeq = Stage::max('sequence') ?? 0;

        Stage::create([
            'code'       => trim($this->newCode),
            'label'      => trim($this->newLabel),
            'bg_color'   => $this->newBgColor,
            'text_color' => $this->newTextColor,
            'sequence'   => $maxSeq + 1,
        ]);

        $this->newCode      = '';
        $this->newLabel     = '';
        $this->newBgColor   = '#E0DDD2';
        $this->newTextColor = '#2C322A';
        $this->dispatch('toast', message: 'Stage creato.');
    }

    public function startEdit(int $id): void
    {
        $stage              = Stage::findOrFail($id);
        $this->editingId    = $id;
        $this->editLabel    = $stage->label;
        $this->editBgColor  = $stage->bg_color;
        $this->editTextColor = $stage->text_color;
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editLabel'     => 'required|string|max:255',
            'editBgColor'   => 'required|string|max:7',
            'editTextColor' => 'required|string|max:7',
        ]);

        Stage::where('id', $this->editingId)->update([
            'label'      => trim($this->editLabel),
            'bg_color'   => $this->editBgColor,
            'text_color' => $this->editTextColor,
        ]);

        $this->editingId = null;
        $this->dispatch('toast', message: 'Stage aggiornato.');
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
    }

    public function moveUp(int $id): void
    {
        $stage = Stage::findOrFail($id);
        $prev  = Stage::where('sequence', '<', $stage->sequence)->orderByDesc('sequence')->first();
        if ($prev) {
            [$stage->sequence, $prev->sequence] = [$prev->sequence, $stage->sequence];
            $stage->save();
            $prev->save();
        }
    }

    public function moveDown(int $id): void
    {
        $stage = Stage::findOrFail($id);
        $next  = Stage::where('sequence', '>', $stage->sequence)->orderBy('sequence')->first();
        if ($next) {
            [$stage->sequence, $next->sequence] = [$next->sequence, $stage->sequence];
            $stage->save();
            $next->save();
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmDeleteId = $id;
        $this->deleteError     = null;
    }

    public function delete(int $id): void
    {
        $stage = Stage::withCount('tasks')->findOrFail($id);

        if ($stage->tasks_count > 0) {
            $this->deleteError = "Usato da {$stage->tasks_count} task: riassegna prima.";
            return;
        }

        $stage->delete();
        $this->confirmDeleteId = null;
        $this->deleteError     = null;
        $this->dispatch('toast', message: 'Stage eliminato.');
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
        $this->deleteError     = null;
    }

    public function render()
    {
        $stages = Stage::orderBy('sequence')->get();

        return view('livewire.settings.stage', compact('stages'))
            ->layout('layouts.app', ['title' => 'Stage']);
    }
}
