<?php

namespace App\Livewire;

use App\Models\Area;
use App\Models\Attachment;
use App\Models\Entry;
use App\Models\Post;
use App\Services\MediaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class DiarioIndex extends Component
{
    // Filtro e paginazione
    public ?int   $filterAreaId = null;
    public string $search       = '';
    public int    $limit        = 30;

    // Lettura (modale): la scrittura/modifica avviene nella pagina dedicata DiarioEditor.
    public ?Entry $readEntry = null;

    // Componi dal diario
    public bool  $selecting   = false;
    public array $selectedIds = [];

    public function openRead(int $id): void
    {
        $this->readEntry = Entry::with(['area', 'attachments.media', 'tags'])->find($id);
    }

    public function closeRead(): void
    {
        $this->readEntry = null;
    }

    public function deleteEntry(int $id): void
    {
        $entry = Entry::with('attachments.media')->findOrFail($id);
        foreach ($entry->attachments as $att) {
            $media = $att->media;
            $att->delete();
            // Rimuove il file solo se non e' usato altrove (es. un post composto
            // da questa voce condivide gli stessi media).
            if ($media && $media->attachments()->count() === 0) {
                app(MediaService::class)->delete($media);
            }
        }
        $entry->delete();

        if ($this->readEntry && $this->readEntry->id === $id) {
            $this->readEntry = null;
        }
        $this->dispatch('toast', message: 'Voce eliminata.');
    }

    public function loadMore(): void
    {
        $this->limit += 20;
    }

    // ===== COMPONI DAL DIARIO =====

    public function toggleSelecting(): void
    {
        $this->selecting   = !$this->selecting;
        $this->selectedIds = [];
    }

    public function toggleSelect(int $id): void
    {
        if (in_array($id, $this->selectedIds)) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, [$id]));
        } else {
            $this->selectedIds[] = $id;
        }
    }

    public function composePost()
    {
        if (empty($this->selectedIds)) return;

        // Voci in ordine cronologico (per la narrazione continua)
        $entries = Entry::whereIn('id', $this->selectedIds)
            ->with('attachments.media')
            ->orderBy('entry_date')
            ->orderByRaw("CASE WHEN entry_time IS NULL THEN 1 ELSE 0 END")
            ->orderBy('entry_time')
            ->get();

        if ($entries->isEmpty()) return;

        // Prosa: testi uniti con righe vuote, senza intestazioni
        $content = $entries->pluck('content')->map(fn ($c) => trim($c))->filter()->implode("\n\n");

        // Area: solo se tutte le voci la condividono
        $areaIds = $entries->pluck('area_id')->unique();
        $areaId  = ($areaIds->count() === 1 && $areaIds->first()) ? $areaIds->first() : null;

        $post = Post::create([
            'user_id'      => Auth::id(),
            'area_id'      => $areaId,
            'title'        => 'Senza titolo',
            'slug'         => 'bozza-' . Str::random(8),
            'content'      => $content,
            'visibility'   => 'draft',
            'published_at' => $entries->max('entry_date'),
        ]);

        // Pivot entry_post (tracciabilita')
        $post->entries()->attach($entries->pluck('id')->all());

        // Set fotografico: stesse foto delle voci (media condiviso), in ordine cronologico
        $seq = 0;
        foreach ($entries as $entry) {
            foreach ($entry->attachments as $att) {
                Attachment::create([
                    'attachable_type' => Post::class,
                    'attachable_id'   => $post->id,
                    'media_id'        => $att->media_id,
                    'sequence'        => $seq++,
                ]);
            }
        }

        $this->selecting   = false;
        $this->selectedIds = [];

        return $this->redirectRoute('blog.edit', ['post' => $post->id], navigate: true);
    }

    public function render()
    {
        $query = Entry::with(['area', 'attachments.media'])
            ->when($this->filterAreaId, fn($q) => $q->where('area_id', $this->filterAreaId))
            ->when($this->search, fn($q) => $q->where('content', 'like', '%' . $this->search . '%'))
            ->orderByDesc('entry_date')
            ->orderByRaw("CASE WHEN entry_time IS NULL THEN 1 ELSE 0 END")
            ->orderByDesc('entry_time')
            ->orderByDesc('id');

        $total   = $query->count();
        $entries = $query->limit($this->limit)->get();
        $hasMore = $entries->count() < $total;

        $byMonth = $entries->groupBy(fn ($e) => $e->entry_date->format('Y-m'))->sortKeysDesc();
        $aree    = Area::orderBy('name')->get();

        return view('livewire.diario-index', compact('byMonth', 'aree', 'hasMore'))
            ->layout('layouts.app', ['title' => 'Diario']);
    }
}
