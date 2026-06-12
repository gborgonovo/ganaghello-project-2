<?php

namespace App\Livewire;

use App\Models\Area;
use App\Models\Entry;
use App\Models\Stage;
use App\Models\Task;
use App\Models\TaskUpdate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AreaDetail extends Component
{
    public Area $area;

    // Inline edit area
    public bool   $editingName   = false;
    public string $areaName      = '';
    public string $areaStatus    = '';
    public string $areaColor     = '';

    // Storia
    public string $storiaFilter  = 'tutto';
    public int    $storiaLimit   = 20;

    // Task tree: array di id con sottoalbero espanso
    public array  $expanded      = [];

    // Quick add task
    public string $quickTaskTitle = '';

    // Quick annota
    public bool   $addingEntry   = false;
    public string $entryContent  = '';

    public function mount(Area $area): void
    {
        $this->area      = $area;
        $this->areaName  = $area->name;
        $this->areaStatus = $area->status ?? '';
        $this->areaColor  = $area->color ?? '';
    }

    // Inline edit
    public function saveName(): void
    {
        $this->validate(['areaName' => 'required|string|max:100']);
        $this->area->update(['name' => trim($this->areaName)]);
        $this->editingName = false;
        $this->area->refresh();
    }

    public function saveStatus(): void
    {
        $this->area->update(['status' => $this->areaStatus ?: null]);
        $this->area->refresh();
    }

    public function saveColor(): void
    {
        $this->area->update(['color' => $this->areaColor ?: null]);
        $this->area->refresh();
    }

    // Task tree
    public function toggleExpand(int $taskId): void
    {
        if (in_array($taskId, $this->expanded)) {
            $this->expanded = array_values(array_filter($this->expanded, fn($id) => $id !== $taskId));
        } else {
            $this->expanded[] = $taskId;
        }
    }

    // Quick add task nell'area
    public function quickAddTask(): void
    {
        $title = trim($this->quickTaskTitle);
        if (!$title) return;

        Task::create([
            'user_id'  => Auth::id(),
            'title'    => $title,
            'stage_id' => Stage::where('code', 'idea')->value('id'),
            'area_id'  => $this->area->id,
        ]);

        $this->quickTaskTitle = '';
        $this->dispatch('task-created');
    }

    // Quick annota (entry di diario per quest'area)
    public function saveEntry(): void
    {
        $content = trim($this->entryContent);
        if (!$content) return;

        Entry::create([
            'user_id'    => Auth::id(),
            'area_id'    => $this->area->id,
            'content'    => $content,
            'entry_date' => today(),
        ]);

        $this->entryContent = '';
        $this->addingEntry  = false;
    }

    public function loadMore(): void
    {
        $this->storiaLimit += 20;
    }

    private function getDescendantAreaIds(): array
    {
        $all  = Area::all()->keyBy('id');
        $ids  = [];
        $stack = $this->area->children->pluck('id')->toArray();
        while ($stack) {
            $id     = array_shift($stack);
            $ids[]  = $id;
            $children = $all->where('parent_area_id', $id)->pluck('id')->toArray();
            $stack  = array_merge($stack, $children);
        }
        return $ids;
    }

    private function buildStoria(): Collection
    {
        $descendantIds = $this->getDescendantAreaIds();
        $allAreaIds    = array_merge([$this->area->id], $descendantIds);
        $allTaskIds    = Task::whereIn('area_id', $allAreaIds)->pluck('id');

        $items = collect();

        // Voci di diario (area + sotto-aree)
        if (in_array($this->storiaFilter, ['tutto', 'diario'])) {
            Entry::whereIn('area_id', $allAreaIds)
                ->with('area')
                ->latest('entry_date')
                ->get()
                ->each(fn($e) => $items->push([
                    'tipo'   => 'diario',
                    'data'   => $e->entry_date,
                    'obj'    => $e,
                    'area'   => $e->area,
                ]));
        }

        // Aggiornamenti task (area + sotto-aree)
        if (in_array($this->storiaFilter, ['tutto', 'aggiornamenti'])) {
            TaskUpdate::whereIn('task_id', $allTaskIds)
                ->with('task.area')
                ->latest()
                ->get()
                ->each(fn($u) => $items->push([
                    'tipo'   => 'aggiornamento',
                    'data'   => $u->created_at,
                    'obj'    => $u,
                    'area'   => $u->task?->area,
                ]));
        }

        // Task completati (area + sotto-aree)
        if (in_array($this->storiaFilter, ['tutto', 'fatti'])) {
            $doneId = Stage::where('code', 'done')->value('id');
            Task::whereIn('area_id', $allAreaIds)
                ->with('area')
                ->where('stage_id', $doneId)
                ->whereNotNull('completed_at')
                ->latest('completed_at')
                ->get()
                ->each(fn($t) => $items->push([
                    'tipo'   => 'fatto',
                    'data'   => $t->completed_at,
                    'obj'    => $t,
                    'area'   => $t->area,
                ]));
        }

        return $items->sortByDesc('data')->values()->take($this->storiaLimit);
    }

    public function render()
    {
        // Task aperti ad albero (solo top-level, non completati/archiviati)
        $closedIds = Stage::whereIn('code', ['done', 'archiviato'])->pluck('id');

        $this->area->load([
            'children' => function ($q) use ($closedIds) {
                $q->withCount([
                    'tasks',
                    'tasks as open_tasks_count' => fn($q2) => $q2->whereNotIn('stage_id', $closedIds),
                ])->with('children')->orderBy('sequence');
            },
            'parent',
        ]);
        $taskTree  = Task::with(['stage', 'children' => function ($q) use ($closedIds) {
            $q->with('stage')->orderBy('sequence');
        }])
            ->where('area_id', $this->area->id)
            ->whereNull('parent_task_id')
            ->whereNotIn('stage_id', $closedIds)
            ->orderBy('sequence')
            ->orderBy('id')
            ->get();

        // Scadenze imminenti (task dell'area con due_date)
        $scadenze = Task::where('area_id', $this->area->id)
            ->whereNotIn('stage_id', $closedIds)
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        // Costi aggregati
        $costMin  = $this->area->tasks()->whereNotIn('stage_id', $closedIds)->sum('cost_min');
        $costReal = $this->area->tasks()->sum('cost_real');

        // Storia
        $storia    = $this->buildStoria();
        $storiaHasMore = $storia->count() >= $this->storiaLimit;

        return view('livewire.area-detail', [
            'taskTree'      => $taskTree,
            'scadenze'      => $scadenze,
            'costMin'       => $costMin,
            'costReal'      => $costReal,
            'storia'        => $storia,
            'storiaHasMore' => $storiaHasMore,
        ])->layout('layouts.app', ['title' => $this->area->name]);
    }
}
