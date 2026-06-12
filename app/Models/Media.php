<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'filename', 'original_filename', 'path', 'folder', 'mime_type', 'size', 'context'];

    const CREATED_AT = 'created_at';

    public function user()       { return $this->belongsTo(User::class); }
    public function attachments(){ return $this->hasMany(Attachment::class); }
    public function expenses()   { return $this->hasMany(Expense::class); }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
}
