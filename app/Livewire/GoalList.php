<?php

namespace App\Livewire;

use App\Models\Goal;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class GoalList extends Component
{
    // Edit
    public ?int   $editingId    = null;
    public string $editTitle    = '';
    public string $editDesc     = '';
    public string $editDeadline = '';
    public string $editStatus   = 'active';

    // Crea
    public bool   $showCreate    = false;
    public string $newTitle      = '';
    public string $newDesc       = '';
    public string $newDeadline   = '';
    public ?int   $newParentId   = null;

    // UI
    public ?int   $confirmDeleteId = null;
    public array  $expandedTasks  = [];

    public function startEdit(int $id): void
    {
        $goal              = Goal::findOrFail($id);
        $this->editingId   = $id;
        $this->editTitle   = $goal->title;
        $this->editDesc    = $goal->description ?? '';
        $this->editDeadline = $goal->deadline?->format('Y-m-d') ?? '';
        $this->editStatus  = $goal->status;
    }

    public function saveEdit(): void
    {
        $this->validate(['editTitle' => 'required|string|max:255']);
        Goal::where('id', $this->editingId)->update([
            'title'       => trim($this->editTitle),
            'description' => trim($this->editDesc) ?: null,
            'deadline'    => $this->editDeadline ?: null,
            'status'      => $this->editStatus,
        ]);
        $this->editingId = null;
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
    }

    public function createGoal(): void
    {
        $this->validate(['newTitle' => 'required|string|max:255']);

        $max = Goal::where('parent_goal_id', $this->newParentId)->max('sequence') ?? 0;
        Goal::create([
            'user_id'        => Auth::id(),
            'title'          => trim($this->newTitle),
            'description'    => trim($this->newDesc) ?: null,
            'deadline'       => $this->newDeadline ?: null,
            'parent_goal_id' => $this->newParentId,
            'sequence'       => $max + 1,
        ]);

        $this->showCreate  = false;
        $this->newTitle    = '';
        $this->newDesc     = '';
        $this->newDeadline = '';
        $this->newParentId = null;
    }

    public function deleteGoal(int $id): void
    {
        Goal::findOrFail($id)->delete();
        $this->confirmDeleteId = null;
    }

    public function toggleTasks(int $goalId): void
    {
        if (in_array($goalId, $this->expandedTasks)) {
            $this->expandedTasks = array_values(
                array_filter($this->expandedTasks, fn($id) => $id !== $goalId)
            );
        } else {
            $this->expandedTasks[] = $goalId;
        }
    }

    public function render()
    {
        $goals = Goal::whereNull('parent_goal_id')
            ->with([
                'tasks', 'tasks.area', 'tasks.stage',
                'children', 'children.tasks', 'children.tasks.area', 'children.tasks.stage',
            ])
            ->orderBy('sequence')
            ->get();

        // Per il select "sotto-goal di..." nel form crea
        $allGoals = Goal::whereNull('parent_goal_id')
            ->orderBy('sequence')
            ->pluck('title', 'id');

        return view('livewire.goal-list', compact('goals', 'allGoals'))
            ->layout('layouts.app', ['title' => 'Goals']);
    }
}
