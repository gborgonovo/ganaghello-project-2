<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskUpdate extends Model
{
    use SoftDeletes;

    protected $fillable = ['task_id', 'content'];

    public function task() { return $this->belongsTo(Task::class); }
}
