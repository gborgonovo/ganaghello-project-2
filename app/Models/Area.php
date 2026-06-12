<?php

namespace App\Models;

use App\Models\Concerns\HasMnemosyneNode;
use App\Observers\AreaObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(AreaObserver::class)]
class Area extends Model
{
    use HasMnemosyneNode;

    protected $fillable = ['name', 'description', 'color', 'status', 'lat', 'lng', 'parent_area_id', 'sequence', 'mnemosyne_node_name'];

    public function mnemosyneLabel(): string
    {
        return (string) $this->name;
    }

    public function parent()   { return $this->belongsTo(Area::class, 'parent_area_id'); }
    public function children() { return $this->hasMany(Area::class, 'parent_area_id')->orderBy('sequence'); }
    public function tasks()    { return $this->hasMany(Task::class); }
    public function entries()  { return $this->hasMany(Entry::class); }
    public function posts()    { return $this->hasMany(Post::class); }
    public function notes()    { return $this->hasMany(Note::class); }
    public function inspirations() { return $this->hasMany(Inspiration::class); }

    public function tags() { return $this->morphToMany(Tag::class, 'taggable'); }
}
