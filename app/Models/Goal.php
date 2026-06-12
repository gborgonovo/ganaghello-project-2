<?php

namespace App\Models;

use App\Models\Concerns\HasMnemosyneNode;
use App\Observers\GoalObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(GoalObserver::class)]
class Goal extends Model
{
    use SoftDeletes, HasMnemosyneNode;

    public function mnemosyneLabel(): string
    {
        return (string) $this->title;
    }

    protected $fillable = [
        'user_id', 'title', 'description', 'status', 'deadline',
        'parent_goal_id', 'sequence', 'mnemosyne_node_name',
    ];

    protected function casts(): array
    {
        return ['deadline' => 'date'];
    }

    public function user()   { return $this->belongsTo(User::class); }
    public function parent() { return $this->belongsTo(Goal::class, 'parent_goal_id'); }
    public function children(){ return $this->hasMany(Goal::class, 'parent_goal_id')->orderBy('sequence'); }
    public function tasks()  { return $this->belongsToMany(Task::class, 'goal_task'); }

    public function committedCost(): float
    {
        return $this->tasks()
            ->whereHas('stage', fn ($q) => $q->whereIn('code', ['approvato', 'todo', 'doing', 'in_attesa', 'done']))
            ->sum('cost_min') ?? 0;
    }

    public function potentialCost(): float
    {
        return $this->tasks()->sum('cost_min') ?? 0;
    }
}
