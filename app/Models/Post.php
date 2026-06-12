<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'area_id', 'title', 'slug', 'excerpt', 'cover',
        'content', 'visibility', 'published_at', 'mnemosyne_node_name',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function user()       { return $this->belongsTo(User::class); }
    public function area()       { return $this->belongsTo(Area::class); }
    public function entries()    { return $this->belongsToMany(Entry::class, 'entry_post'); }
    public function tags()       { return $this->morphToMany(Tag::class, 'taggable'); }
    public function attachments(){ return $this->morphMany(Attachment::class, 'attachable')->orderBy('sequence'); }

    public function isPublic(): bool { return $this->visibility === 'public'; }

    /** Il media di copertina: la prima foto allegata (sequence piu' basso), se esiste. */
    public function coverMedia(): ?Media
    {
        return $this->attachments->first()?->media;
    }

    /** True se la copertina e' un'immagine locale (attachment), false se solo URL legacy. */
    public function hasLocalCover(): bool
    {
        return $this->coverMedia() !== null;
    }
}
