<?php

namespace App\Livewire;

use App\Models\Area;
use App\Models\Attachment;
use App\Models\Entry;
use App\Models\Media;
use App\Models\Task;
use App\Services\MediaService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Editor del diario a pagina dedicata (focus), per creare e modificare una voce.
 * La lettura avviene altrove (modale nell'index, o pagina DiarioShow): qui si scrive.
 * Rotte: /diario/nuovo (create) e /diario/{entry}/modifica (edit).
 */
class DiarioEditor extends Component
{
    use WithFileUploads;

    public ?Entry  $entry   = null;

    public string  $title   = '';
    public string  $content = '';
    public string  $date    = '';
    public string  $time    = '';
    public ?int    $areaId  = null;
    public string  $kind    = 'auto'; // 'auto' = dedotto dal contenuto; oppure un valore di Entry::KINDS

    /** Task a cui questa voce fa da aggiornamento (pivot task_entry). Sempre facoltativo. */
    public array   $taskIds    = [];
    public string  $taskSearch = '';

    public         $cover             = null;
    public ?string $currentCoverUrl   = null;
    public bool    $removeCover        = false;
    public bool    $showLibrary        = false;
    public string  $libSearch          = '';
    public ?int    $coverMediaId       = null;
    public ?string $selectedLibraryUrl = null;

    public function mount(?Entry $entry = null): void
    {
        if ($entry && $entry->exists) {
            $this->entry   = $entry->load('attachments.media', 'tasks');
            $this->title   = $entry->title ?? '';
            $this->content = $entry->content;
            $this->date    = $entry->entry_date->toDateString();
            $this->time    = $entry->entry_time ? substr($entry->entry_time, 0, 5) : '';
            $this->areaId  = $entry->area_id;
            $this->kind    = in_array($entry->kind, Entry::KINDS, true) ? $entry->kind : 'auto';
            $this->taskIds = $entry->tasks->pluck('id')->all();

            $cover = $entry->attachments->first()?->media;
            $this->currentCoverUrl = $cover ? route('media.serve', [$cover, 'medium']) : null;
        } else {
            // Default proposti in ora italiana (lo storage resta com'e' inserito).
            $this->date = today('Europe/Rome')->toDateString();
            $this->time = now('Europe/Rome')->format('H:i');

            // Arrivo da un task ("Scrivi nel diario"): pre-collega e eredita l'area.
            $preTask = request()->integer('task') ?: null;
            if ($preTask && $task = Task::find($preTask)) {
                $this->taskIds = [$task->id];
                $this->areaId  = $task->area_id;
            }
        }
    }

    public function toggleTask(int $taskId): void
    {
        $this->taskIds = in_array($taskId, $this->taskIds, true)
            ? array_values(array_diff($this->taskIds, [$taskId]))
            : [...$this->taskIds, $taskId];
    }

    protected function rules(): array
    {
        return [
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
            'date'    => 'required|date',
            'time'    => ['nullable', 'regex:/^\d{1,2}:\d{2}$/'],
            'kind'    => ['required', 'in:auto,' . implode(',', Entry::KINDS)],
            'cover'   => 'nullable|image|max:20480',
            'taskIds'   => ['array'],
            'taskIds.*' => ['integer', 'exists:tasks,id'],
        ];
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
        $this->coverMediaId       = $mediaId;
        $this->selectedLibraryUrl = route('media.serve', [$media, 'medium']);
        $this->cover              = null;
        $this->removeCover        = false;
        $this->showLibrary        = false;
    }

    public function dropCover(): void
    {
        $this->removeCover        = true;
        $this->cover              = null;
        $this->coverMediaId       = null;
        $this->selectedLibraryUrl = null;
    }

    public function save()
    {
        $this->validate();

        $hasPhoto = $this->cover || $this->coverMediaId
            || ($this->currentCoverUrl && !$this->removeCover);

        $kind = $this->kind === 'auto'
            ? Entry::defaultKind(trim($this->content), (bool) $hasPhoto)
            : $this->kind;

        $data = [
            'area_id'    => $this->areaId ?: null,
            'title'      => trim($this->title),
            'content'    => trim($this->content),
            'kind'       => $kind,
            'entry_date' => $this->date,
            'entry_time' => $this->time ?: null,
        ];

        if ($this->entry) {
            $this->entry->update($data);
            $entry = $this->entry;
        } else {
            $entry = Entry::create($data + ['user_id' => Auth::id()]);
        }

        // Task a cui la voce fa da aggiornamento (pivot task_entry).
        $entry->tasks()->sync($this->taskIds);

        // Sostituisci/rimuovi la copertina solo se e' cambiata.
        if ($this->removeCover || $this->cover || $this->coverMediaId) {
            $entry->load('attachments.media');
            $entry->attachments->each(fn ($att) => $att->delete());
        }

        if ($this->cover) {
            $media = app(MediaService::class)->store($this->cover);
            Attachment::create([
                'attachable_type' => Entry::class,
                'attachable_id'   => $entry->id,
                'media_id'        => $media->id,
                'sequence'        => 1,
            ]);
        }

        if ($this->coverMediaId && !$this->removeCover) {
            Attachment::create([
                'attachable_type' => Entry::class,
                'attachable_id'   => $entry->id,
                'media_id'        => $this->coverMediaId,
                'sequence'        => 1,
            ]);
        }

        $this->dispatch('toast', message: $this->entry ? 'Voce aggiornata.' : 'Voce salvata.');

        return $this->redirectRoute('diario', navigate: true);
    }

    public function render()
    {
        $aree = Area::orderBy('name')->get();

        // Lista corta: i task dell'area scelta (se c'e'), piu' quelli gia' collegati e
        // quelli che matchano la ricerca. Gli archiviati restano fuori.
        $tasksCollegabili = Task::with('stage')
            ->whereHas('stage', fn ($q) => $q->where('code', '!=', 'archiviato'))
            ->where(function ($q) {
                $q->when($this->areaId, fn ($qq) => $qq->where('area_id', $this->areaId));
                if ($this->taskIds)    $q->orWhereIn('id', $this->taskIds);
                if ($this->taskSearch) $q->orWhere('title', 'like', '%' . $this->taskSearch . '%');
            })
            ->orderBy('title')
            ->limit(60)
            ->get();

        $libraryImages = $this->showLibrary
            ? Media::where('mime_type', 'like', 'image/%')
                ->when($this->libSearch, fn ($q) => $q->where('original_filename', 'like', '%' . $this->libSearch . '%'))
                ->orderByDesc('created_at')->limit(48)->get()
            : collect();

        return view('livewire.diario-editor', compact('aree', 'libraryImages', 'tasksCollegabili'))
            ->layout('layouts.app', ['title' => $this->entry ? 'Modifica voce' : 'Nuova voce']);
    }
}
