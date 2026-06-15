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

    /** Tipi di voce per la resa scrapbook. Estensibile: aggiungi un valore + il partial in livewire/diario/entry. */
    public const KINDS = ['polaroid', 'postit', 'nota'];

    /** Oltre questa lunghezza di contenuto, una voce senza foto nasce come "nota" anziché "postit". */
    public const NOTA_THRESHOLD = 280;

    public function mnemosyneLabel(): string
    {
        return $this->title ?: Str::limit((string) $this->content, 60, '');
    }

    protected $fillable = ['user_id', 'area_id', 'title', 'content', 'kind', 'entry_date', 'entry_time', 'mnemosyne_node_name'];

    /** Tipo di default in base al contenuto: con foto -> polaroid, testo lungo -> nota, altrimenti postit. */
    public static function defaultKind(string $content, bool $hasPhoto): string
    {
        return match (true) {
            $hasPhoto                                  => 'polaroid',
            mb_strlen($content) > self::NOTA_THRESHOLD => 'nota',
            default                                    => 'postit',
        };
    }

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
