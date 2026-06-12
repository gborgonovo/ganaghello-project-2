<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    public $timestamps = false;

    protected $fillable = ['media_id', 'attachable_type', 'attachable_id', 'caption', 'sequence'];

    public function media()      { return $this->belongsTo(Media::class); }
    public function attachable() { return $this->morphTo(); }
}
