<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stage extends Model
{
    protected $fillable = ['code', 'label', 'bg_color', 'text_color', 'sequence'];

    public function tasks() { return $this->hasMany(Task::class); }

    public function isDecisional(): bool
    {
        return in_array($this->code, ['idea', 'discussione']);
    }

    public function isActive(): bool
    {
        return in_array($this->code, ['approvato', 'todo', 'doing', 'in_attesa']);
    }
}
