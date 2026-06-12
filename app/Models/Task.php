<?php

namespace App\Models;

use App\Models\Concerns\HasMnemosyneNode;
use App\Observers\TaskObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(TaskObserver::class)]
class Task extends Model
{
    use SoftDeletes, HasMnemosyneNode;

    public function mnemosyneLabel(): string
    {
        return (string) $this->title;
    }

    protected $fillable = [
        'user_id', 'assigned_to', 'title', 'description',
        'stage_id', 'area_id', 'parent_task_id',
        'start_date', 'due_date', 'completed_at',
        'cost_min', 'cost_max', 'cost_basis', 'cost_real',
        'work_estimate', 'executor', 'priority', 'sequence',
        'mnemosyne_node_name',
    ];

    protected function casts(): array
    {
        return [
            'start_date'   => 'date',
            'due_date'     => 'date',
            'completed_at' => 'datetime',
            'cost_min'     => 'float',
            'cost_max'     => 'float',
            'cost_real'    => 'float',
            'work_estimate'=> 'float',
        ];
    }

    public function user()         { return $this->belongsTo(User::class); }
    public function assignedUser() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function stage()        { return $this->belongsTo(Stage::class); }
    public function area()         { return $this->belongsTo(Area::class); }
    public function parent()       { return $this->belongsTo(Task::class, 'parent_task_id'); }
    public function children()     { return $this->hasMany(Task::class, 'parent_task_id')->orderBy('sequence'); }
    public function updates()      { return $this->hasMany(TaskUpdate::class)->latest(); }
    public function latestUpdate() { return $this->hasOne(TaskUpdate::class)->latestOfMany(); }
    public function collaborators(){ return $this->belongsToMany(User::class, 'task_collaborators'); }
    public function expenses()     { return $this->hasMany(Expense::class); }
    public function goals()        { return $this->belongsToMany(Goal::class, 'goal_task'); }
    public function entries()      { return $this->belongsToMany(Entry::class, 'task_entry'); }
    public function tags()         { return $this->morphToMany(Tag::class, 'taggable'); }
    public function attachments()  { return $this->morphMany(Attachment::class, 'attachable')->orderBy('sequence'); }
    public function firstImage()   { return $this->morphOne(Attachment::class, 'attachable')->whereHas('media', fn($q) => $q->where('mime_type', 'like', 'image/%'))->orderBy('sequence'); }

    public function isDone(): bool
    {
        return $this->stage?->code === 'done';
    }

    public function effectiveCost(): ?float
    {
        return $this->cost_real
            ?? ($this->children()->exists() ? $this->children()->sum('cost_min') : $this->cost_min);
    }
}
