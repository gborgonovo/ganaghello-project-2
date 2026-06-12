<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = ['task_id', 'description', 'amount', 'expense_date', 'notes', 'media_id'];

    protected function casts(): array
    {
        return [
            'amount'       => 'float',
            'expense_date' => 'date',
        ];
    }

    public function task()  { return $this->belongsTo(Task::class); }
    public function media() { return $this->belongsTo(Media::class); }
}
