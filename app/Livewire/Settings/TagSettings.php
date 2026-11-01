<?php

namespace App\Livewire\Settings;

use App\Actions\TagActions;
use App\Exceptions\RegolaDominio;
use App\Models\Tag;
use Illuminate\Support\Facades\Auth;
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
            'newName'  => 'required|string|max:100',
            'newColor' => 'nullable|string|max:7',
        ]);

        try {
            app(TagActions::class)->crea(Auth::user(), ['name' => $this->newName, 'color' => $this->newColor]);
        } catch (RegolaDominio $e) {
            $this->addError('newName', $e->getMessage());
            return;
        }

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

        try {
            app(TagActions::class)->modifica(Auth::user(), Tag::findOrFail($this->editingId), ['name' => $this->editName, 'color' => $this->editColor]);
        } catch (RegolaDominio $e) {
            $this->addError('editName', $e->getMessage());
            return;
        }

        $this->editingId = null;
        $this->dispatch('toast', message: 'Tag aggiornato.');
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
    }

    public function delete(int $id): void
    {
        // Eliminazione recuperabile: i collegamenti restano e tornano se il tag viene ripristinato.
        app(TagActions::class)->elimina(Auth::user(), Tag::findOrFail($id));
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
