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

    protected $fillable = ['name', 'description', 'color', 'text_color', 'status', 'lat', 'lng', 'parent_area_id', 'sequence', 'mnemosyne_node_name'];

    public function mnemosyneLabel(): string
    {
        return (string) $this->name;
    }

    /** Sfondo del chip area (fallback su un tono neutro). */
    public function getChipBgColorAttribute(): string
    {
        return $this->color ?: '#E0DDD2';
    }

    /**
     * Testo del chip area: se text_color non e' impostato, lo deriva dalla
     * luminanza dello sfondo (chiaro -> testo scuro, scuro -> testo bianco).
     */
    public function getChipTextColorAttribute(): string
    {
        if ($this->text_color) {
            return $this->text_color;
        }
        $hex = ltrim((string) $this->color, '#');
        if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
            return '#2C322A';
        }
        $lum = (0.299 * hexdec(substr($hex, 0, 2))
              + 0.587 * hexdec(substr($hex, 2, 2))
              + 0.114 * hexdec(substr($hex, 4, 2))) / 255;
        return $lum > 0.6 ? '#2C322A' : '#FFFFFF';
    }

    public function parent()   { return $this->belongsTo(Area::class, 'parent_area_id'); }
    public function children() { return $this->hasMany(Area::class, 'parent_area_id')->orderBy('sequence'); }
    public function tasks()    { return $this->hasMany(Task::class); }
    public function entries()  { return $this->hasMany(Entry::class); }
    public function posts()    { return $this->hasMany(Post::class); }
    public function notes()    { return $this->hasMany(Note::class); }
    public function inspirations() { return $this->hasMany(Inspiration::class); }

    public function tags() { return $this->morphToMany(Tag::class, 'taggable'); }

    /**
     * Opzioni per le <select>, ordinate ad albero: ogni radice seguita dalle sue
     * discendenti, indentate. Restituisce oggetti con `id` e `label`.
     */
    public static function optionsTree(): \Illuminate\Support\Collection
    {
        $byParent = static::orderBy('sequence')->orderBy('name')->get()->groupBy('parent_area_id');
        $out = collect();

        $walk = function ($parentId, int $depth) use (&$walk, $byParent, $out) {
            foreach ($byParent->get($parentId, collect()) as $area) {
                $out->push((object) [
                    'id'    => $area->id,
                    'label' => str_repeat('— ', $depth) . $area->name,
                ]);
                $walk($area->id, $depth + 1);
            }
        };
        $walk(null, 0);

        return $out;
    }
}
