<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = ['name', 'color'];

    protected static function booted(): void
    {
        static::creating(fn ($tag) => $tag->name = strtolower($tag->name));
        static::updating(fn ($tag) => $tag->name = strtolower($tag->name));
    }

    public function tasks()       { return $this->morphedByMany(Task::class, 'taggable'); }
    public function entries()     { return $this->morphedByMany(Entry::class, 'taggable'); }
    public function posts()       { return $this->morphedByMany(Post::class, 'taggable'); }
    public function inspirations(){ return $this->morphedByMany(Inspiration::class, 'taggable'); }
    public function notes()       { return $this->morphedByMany(Note::class, 'taggable'); }

    public function getDisplayNameAttribute(): string
    {
        return ucfirst($this->name);
    }
}
