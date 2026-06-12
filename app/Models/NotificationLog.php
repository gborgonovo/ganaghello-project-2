<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'task_id', 'type', 'payload', 'sent_at'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function task() { return $this->belongsTo(Task::class); }
}
