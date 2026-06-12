<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'area_id', 'title', 'content'];

    public function user()       { return $this->belongsTo(User::class); }
    public function area()       { return $this->belongsTo(Area::class); }
    public function tags()       { return $this->morphToMany(Tag::class, 'taggable'); }
    public function attachments(){ return $this->morphMany(Attachment::class, 'attachable')->orderBy('sequence'); }
}
