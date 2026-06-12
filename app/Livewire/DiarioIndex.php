<?php

namespace App\Livewire;

use App\Models\Area;
use App\Models\Attachment;
use App\Models\Entry;
use App\Models\Media;
use App\Models\Post;
use App\Services\MediaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class DiarioIndex extends Component
{
    use WithFileUploads;

    // Filtro e paginazione
    public ?int    $filterAreaId = null;
    public string  $search       = '';
    public int     $limit        = 30;

    // Modale
    public bool    $showModal        = false;
    public ?int    $editEntryId      = null;
    public string  $modalTitle       = '';
    public string  $modalContent     = '';
    public string  $modalDate        = '';
    public string  $modalTime        = '';
    public ?int    $modalAreaId      = null;
    public         $modalCover            = null;
    public ?string $currentCoverUrl       = null;
    public bool    $removeCover           = false;
    public bool    $showLibrary           = false;
    public string  $libSearch             = '';
    public ?int    $modalCoverMediaId     = null;
    public ?string $selectedLibraryUrl    = null;

    // Delete
    public ?int $confirmDeleteId = null;

    // Componi dal diario
    public bool  $selecting   = false;
    public array $selectedIds = [];

    protected function rules(): array
    {
        return [
            'modalTitle'   => 'required|string|max:255',
            'modalContent' => 'required|string',
            'modalDate'    => 'required|date',
            'modalTime'    => ['nullable', 'regex:/^\d{1,2}:\d{2}$/'],
            'modalCover'   => 'nullable|image|max:20480',
        ];
    }

    public function openNew(): void
    {
        $this->resetModal();
        $this->modalDate = today()->toDateString();
        $this->modalTime = now()->format('H:i');
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $entry = Entry::with('attachments.media')->findOrFail($id);

        $this->resetModal();
        $this->editEntryId  = $id;
        $this->modalTitle   = $entry->title ?? '';
        $this->modalContent = $entry->content;
        $this->modalDate    = $entry->entry_date->toDateString();
        $this->modalTime    = $entry->entry_time ? substr($entry->entry_time, 0, 5) : '';
        $this->modalAreaId  = $entry->area_id;

        $cover = $entry->attachments->first()?->media;
        $this->currentCoverUrl = $cover ? route('media.serve', [$cover, 'medium']) : null;

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal  = false;
        $this->modalCover = null;
    }

    public function toggleLibrary(): void
    {
        $this->showLibrary = !$this->showLibrary;
        $this->libSearch   = '';
    }

    public function selectFromLibrary(int $mediaId): void
    {
        $media = Media::find($mediaId);
        if (!$media) return;
        $this->modalCoverMediaId  = $mediaId;
        $this->selectedLibraryUrl = route('media.serve', [$media, 'medium']);
        $this->modalCover         = null;
        $this->removeCover        = false;
        $this->showLibrary        = false;
    }

    public function saveModal(): void
    {
        $this->validate();

        $data = [
            'user_id'    => Auth::id(),
            'area_id'    => $this->modalAreaId ?: null,
            'title'      => trim($this->modalTitle),
            'content'    => trim($this->modalContent),
            'entry_date' => $this->modalDate,
            'entry_time' => $this->modalTime ?: null,
        ];

        if ($this->editEntryId) {
            $entry = Entry::with('attachments.media')->findOrFail($this->editEntryId);
            $entry->update($data);
        } else {
            $entry = Entry::create($data);
        }

        // Rimuovi attachment esistente se rimpiazzato o rimosso
        if ($this->removeCover || $this->modalCover || $this->modalCoverMediaId) {
            $entry->load('attachments.media');
            $entry->attachments->each(fn ($att) => $att->delete());
        }

        // Nuova copertina da upload
        if ($this->modalCover) {
            $media = app(MediaService::class)->store($this->modalCover);
            Attachment::create([
                'attachable_type' => Entry::class,
                'attachable_id'   => $entry->id,
                'media_id'        => $media->id,
                'sequence'        => 1,
            ]);
        }

        // Copertina dalla libreria
        if ($this->modalCoverMediaId && !$this->removeCover) {
            Attachment::create([
                'attachable_type' => Entry::class,
                'attachable_id'   => $entry->id,
                'media_id'        => $this->modalCoverMediaId,
                'sequence'        => 1,
            ]);
        }

        $this->showModal  = false;
        $this->modalCover = null;
    }

    public function deleteEntry(int $id): void
    {
        $entry = Entry::with('attachments.media')->findOrFail($id);
        $entry->attachments->each(function ($att) {
            app(MediaService::class)->delete($att->media);
            $att->delete();
        });
        $entry->delete();
        $this->confirmDeleteId = null;
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
        $entries = Entry::where('user_id', Auth::id())
            ->whereIn('id', $this->selectedIds)
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
        $query = Entry::where('user_id', Auth::id())
            ->with(['area', 'attachments.media'])
            ->when($this->filterAreaId, fn($q) => $q->where('area_id', $this->filterAreaId))
            ->when($this->search, fn($q) => $q->where('content', 'like', '%' . $this->search . '%'))
            ->orderByDesc('entry_date')
            ->orderByRaw("CASE WHEN entry_time IS NULL THEN 1 ELSE 0 END")
            ->orderByDesc('entry_time')
            ->orderByDesc('id');

        $total   = $query->count();
        $entries = $query->limit($this->limit)->get();
        $hasMore = $entries->count() < $total;

        $byDate = $entries->groupBy(fn ($e) => $e->entry_date->format('Y-m-d'))->sortKeysDesc();
        $aree   = Area::orderBy('name')->get();

        $libraryImages = $this->showLibrary
            ? Media::where('mime_type', 'like', 'image/%')
                ->when($this->libSearch, fn ($q) => $q->where('original_filename', 'like', '%'.$this->libSearch.'%'))
                ->orderByDesc('created_at')
                ->limit(48)
                ->get()
            : collect();

        return view('livewire.diario-index', compact('byDate', 'aree', 'hasMore', 'libraryImages'))
            ->layout('layouts.app', ['title' => 'Diario']);
    }

    private function resetModal(): void
    {
        $this->editEntryId        = null;
        $this->modalTitle         = '';
        $this->modalContent       = '';
        $this->modalDate          = '';
        $this->modalTime          = '';
        $this->modalAreaId        = null;
        $this->modalCover         = null;
        $this->currentCoverUrl    = null;
        $this->removeCover        = false;
        $this->showLibrary        = false;
        $this->libSearch          = '';
        $this->modalCoverMediaId  = null;
        $this->selectedLibraryUrl = null;
        $this->confirmDeleteId    = null;
        $this->resetValidation();
    }
}
