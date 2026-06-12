<?php

namespace App\Livewire;

use App\Models\Area;
use App\Models\Goal;
use App\Models\Stage;
use App\Models\Tag;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TaskForm extends Component
{
    public bool $open     = false;
    public bool $expanded = false;

    // Campi base
    public string  $title       = '';
    public string  $description = '';
    public ?int    $stage_id    = null;
    public ?int    $area_id     = null;

    // Campi espansi
    public string  $priority       = '';
    public string  $due_date       = '';
    public string  $start_date     = '';
    public string  $executor       = '';
    public string  $work_estimate  = '';
    public string  $cost_min       = '';
    public string  $cost_max       = '';
    public string  $cost_basis     = '';
    public ?int    $parent_task_id = null;
    public string  $tagInput       = '';
    public array   $tagIds         = [];
    public array   $goalIds        = [];

    // Pre-fill contestuale
    public ?int $preAreaId  = null;
    public ?int $preStageId = null;

    protected function rules(): array
    {
        $stageCode = $this->stage_id
            ? Stage::find($this->stage_id)?->code
            : null;
        $dueDateRequired = in_array($stageCode, ['todo', 'doing', 'in_attesa', 'done']);

        return [
            'title'    => 'required|string|max:255',
            'stage_id' => 'nullable|exists:stages,id',
            'area_id'  => 'nullable|exists:areas,id',
            'due_date' => $dueDateRequired ? 'required|date' : 'nullable|date',
            'cost_min' => 'nullable|numeric|min:0',
            'cost_max' => 'nullable|numeric|min:0',
        ];
    }

    public function mount(?int $areaId = null, ?int $stageId = null): void
    {
        $this->preAreaId  = $areaId;
        $this->preStageId = $stageId;
        $this->resetForm();
    }

    public function openModal(?int $areaId = null, ?int $stageId = null): void
    {
        $this->resetForm();
        if ($areaId)  $this->area_id  = $areaId;
        if ($stageId) $this->stage_id = $stageId;
        $this->open = true;
    }

    public function closeModal(): void
    {
        $this->open     = false;
        $this->expanded = false;
        $this->resetForm();
    }

    public function toggleExpanded(): void
    {
        $this->expanded = !$this->expanded;
    }

    public function addTag(): void
    {
        $name = strtolower(trim($this->tagInput));
        if (!$name) return;

        $tag = Tag::firstOrCreate(['name' => $name]);
        if (!in_array($tag->id, $this->tagIds)) {
            $this->tagIds[] = $tag->id;
        }
        $this->tagInput = '';
    }

    public function removeTag(int $id): void
    {
        $this->tagIds = array_values(array_filter($this->tagIds, fn ($t) => $t !== $id));
    }

    public function save(): void
    {
        $this->validate();

        $task = Task::create([
            'user_id'        => Auth::id(),
            'title'          => trim($this->title),
            'description'    => trim($this->description) ?: null,
            'stage_id'       => $this->stage_id ?? Stage::where('code', 'idea')->value('id'),
            'area_id'        => $this->area_id ?: null,
            'parent_task_id' => $this->parent_task_id,
            'priority'       => $this->priority ?: null,
            'due_date'       => $this->due_date ?: null,
            'start_date'     => $this->start_date ?: null,
            'executor'       => $this->executor ?: null,
            'work_estimate'  => $this->work_estimate !== '' ? (float) $this->work_estimate : null,
            'cost_min'       => $this->cost_min !== '' ? (float) $this->cost_min : null,
            'cost_max'       => $this->cost_max !== '' ? (float) $this->cost_max : null,
            'cost_basis'     => $this->cost_basis ?: null,
        ]);

        if ($this->tagIds) {
            $task->tags()->sync($this->tagIds);
        }
        if ($this->goalIds) {
            $task->goals()->sync($this->goalIds);
            // Le relazioni del task verso i goal sono note solo ora: risincronizza.
            if (config('services.mnemosyne.sync', true)) {
                \App\Jobs\SyncTaskToMnemosyne::dispatch($task->id);
            }
        }

        $this->closeModal();
        $this->dispatch('task-created', taskId: $task->id);
    }

    private function resetForm(): void
    {
        $this->title        = '';
        $this->description  = '';
        $this->stage_id     = $this->preStageId ?? Stage::where('code', 'idea')->value('id');
        $this->area_id      = $this->preAreaId;
        $this->priority     = '';
        $this->due_date     = '';
        $this->start_date   = '';
        $this->executor     = '';
        $this->work_estimate = '';
        $this->cost_min     = '';
        $this->cost_max     = '';
        $this->cost_basis   = '';
        $this->parent_task_id = null;
        $this->tagInput     = '';
        $this->tagIds       = [];
        $this->goalIds      = [];
    }

    public function render()
    {
        return view('livewire.task-form', [
            'stages' => Stage::orderBy('sequence')->get(),
            'areas'  => Area::orderBy('sequence')->get(),
            'goals'  => Goal::where('status', 'active')->orderBy('title')->get(),
            'selectedTags' => Tag::whereIn('id', $this->tagIds)->get(),
        ]);
    }
}
