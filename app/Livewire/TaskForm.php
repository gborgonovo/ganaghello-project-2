<?php

namespace App\Livewire;

use App\Actions\TagActions;
use App\Actions\TaskActions;
use App\Models\Area;
use App\Models\Goal;
use App\Models\Stage;
use App\Models\Tag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class TaskForm extends Component
{
    private const CAMPI_DETTAGLI = ['priority', 'due_date', 'start_date', 'executor', 'work_estimate', 'cost_min', 'cost_max', 'cost_basis'];

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
        return [
            'title'    => 'required|string|max:255',
            'stage_id' => 'nullable|exists:stages,id',
            'area_id'  => 'nullable|exists:areas,id',
            'due_date' => 'nullable|date',
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

        $tag = app(TagActions::class)->trovaOCrea(Auth::user(), $name);
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
        try {
            $this->salva();
        } catch (ValidationException $e) {
            // I campi dei dettagli sono nascosti finché il form è compatto: aprirlo rende visibile l'errore.
            if (array_intersect(array_keys($e->errors()), self::CAMPI_DETTAGLI)) {
                $this->expanded = true;
            }
            throw $e;
        }
    }

    private function salva(): void
    {
        $this->validate();

        $task = app(TaskActions::class)->crea(Auth::user(), [
            'title'          => $this->title,
            'description'    => $this->description,
            'stage_id'       => $this->stage_id,
            'area_id'        => $this->area_id,
            'parent_task_id' => $this->parent_task_id,
            'priority'       => $this->priority,
            'due_date'       => $this->due_date,
            'start_date'     => $this->start_date,
            'executor'       => $this->executor,
            'work_estimate'  => $this->work_estimate,
            'cost_min'       => $this->cost_min,
            'cost_max'       => $this->cost_max,
            'cost_basis'     => $this->cost_basis,
            'tag'            => Tag::whereIn('id', $this->tagIds)->pluck('name')->all(),
            'goal_ids'       => $this->goalIds,
        ]);

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
