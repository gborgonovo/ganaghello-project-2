<?php

namespace App\Models;

use App\Models\Concerns\HasMnemosyneNode;
use App\Observers\EntryObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[ObservedBy(EntryObserver::class)]
class Entry extends Model
{
    use SoftDeletes, HasMnemosyneNode;

    public function mnemosyneLabel(): string
    {
        return $this->title ?: Str::limit((string) $this->content, 60, '');
    }

    protected $fillable = ['user_id', 'area_id', 'title', 'content', 'entry_date', 'entry_time', 'mnemosyne_node_name'];

    protected function casts(): array
    {
        return ['entry_date' => 'date'];
    }

    public function user()       { return $this->belongsTo(User::class); }
    public function area()       { return $this->belongsTo(Area::class); }
    public function posts()      { return $this->belongsToMany(Post::class, 'entry_post'); }
    public function tasks()      { return $this->belongsToMany(Task::class, 'task_entry'); }
    public function tags()       { return $this->morphToMany(Tag::class, 'taggable'); }
    public function attachments(){ return $this->morphMany(Attachment::class, 'attachable')->orderBy('sequence'); }
}
