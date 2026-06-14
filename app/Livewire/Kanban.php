<?php

namespace App\Livewire;

use App\Models\Area;
use App\Models\Goal;
use App\Models\Stage;
use App\Models\Tag;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Kanban extends Component
{
    public ?int   $filterArea     = null;
    public ?int   $filterGoal     = null;
    public array  $filterTags     = [];
    public string $filterScadenza = '';
    public bool   $filterWeekend  = false;
    public string $search         = '';
    public bool  $showDecisione = false;
    public bool  $showArchivio  = false;

    public array $quickAdd = [];

    public function mount(): void
    {
        $this->quickAdd = Stage::pluck('id')
            ->mapWithKeys(fn($id) => [$id => ''])
            ->toArray();
    }

    public function moveTask(int $taskId, int $stageId): void
    {
        $task  = Task::findOrFail($taskId);
        $stage = Stage::findOrFail($stageId);

        $updates = ['stage_id' => $stageId];

        if (in_array($stage->code, ['todo', 'doing', 'in_attesa', 'done']) && !$task->due_date) {
            $updates['due_date'] = today();
        }
        if ($stage->code === 'done' && !$task->completed_at) {
            $updates['completed_at'] = now();
        } elseif ($stage->code !== 'done' && $task->completed_at) {
            $updates['completed_at'] = null;
        }

        $task->update($updates);
    }

    public function stepForward(int $taskId): void
    {
        $task  = Task::with('stage')->findOrFail($taskId);
        $next  = Stage::where('sequence', '>', $task->stage->sequence)
            ->orderBy('sequence')
            ->first();
        if ($next) {
            $this->moveTask($taskId, $next->id);
        }
    }

    public function stepBackward(int $taskId): void
    {
        $task  = Task::with('stage')->findOrFail($taskId);
        $prev  = Stage::where('sequence', '<', $task->stage->sequence)
            ->orderBy('sequence', 'desc')
            ->first();
        if ($prev) {
            $this->moveTask($taskId, $prev->id);
        }
    }

    public function quickAddTask(int $stageId): void
    {
        $title = trim($this->quickAdd[$stageId] ?? '');
        if (!$title) return;

        $stage   = Stage::findOrFail($stageId);
        $updates = [
            'user_id'  => Auth::id(),
            'title'    => $title,
            'stage_id' => $stageId,
        ];

        if (in_array($stage->code, ['todo', 'doing', 'in_attesa', 'done'])) {
            $updates['due_date'] = today();
        }

        Task::create($updates);
        $this->quickAdd[$stageId] = '';
    }

    public function render()
    {
        $zoneMap = [
            'esecuzione' => ['todo', 'doing', 'in_attesa', 'done'],
            'decisione'  => ['approvato', 'discussione', 'idea'],
            'archivio'   => ['archiviato'],
        ];

        $allStages = Stage::orderBy('sequence')->get()->keyBy('code');

        $zones = collect($zoneMap)->map(
            fn($codes) => collect($codes)->map(fn($code) => $allStages->get($code))->filter()->values()
        );

        $tasks = Task::with(['stage', 'area', 'parent', 'latestUpdate', 'firstImage.media'])
            ->withCount('children')
            ->when($this->filterArea, fn($q) => $q->where('area_id', $this->filterArea))
            ->when($this->filterGoal, fn($q) => $q->whereHas('goals', fn($gq) => $gq->where('goals.id', $this->filterGoal)))
            ->when($this->filterTags, fn($q) => $q->whereHas('tags', fn($tq) => $tq->whereIn('tags.id', $this->filterTags)))
            ->when($this->filterScadenza === 'scaduti', fn($q) => $q->whereNotNull('due_date')->whereDate('due_date', '<', today()))
            ->when($this->filterScadenza === '7gg', fn($q) => $q->whereNotNull('due_date')->whereDate('due_date', '<=', today()->addDays(7)))
            ->when($this->filterScadenza === '30gg', fn($q) => $q->whereNotNull('due_date')->whereDate('due_date', '<=', today()->addDays(30)))
            ->when($this->filterWeekend, fn($q) => $q->whereIn('executor', ['una_persona', 'team'])
                ->whereNotNull('due_date')->whereDate('due_date', '<=', today()->endOfWeek()))
            ->when($this->search, fn($q) => $q->where('title', 'like', '%' . $this->search . '%'))
            ->orderBy('sequence')
            ->orderBy('id')
            ->get()
            ->groupBy('stage_id');

        return view('livewire.kanban', [
            'zones'  => $zones,
            'tasks'  => $tasks,
            'areas'  => Area::orderBy('sequence')->get(),
            'goals'  => Goal::where('status', 'active')->orderBy('title')->get(),
            'tags'   => Tag::orderBy('name')->get(),
        ])->layout('layouts.app', ['title' => 'Kanban']);
    }
}
