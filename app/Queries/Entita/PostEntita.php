<?php

namespace App\Queries\Entita;

use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PostEntita extends Entita
{
    protected function model(): string { return Post::class; }

    public function filtri(): array { return ['visibilita', 'area_id', 'includi_sottoaree', 'testo']; }

    protected function relazioniElenco(): array { return ['area']; }

    protected function relazioniDettaglio(): array { return ['area', 'entries', 'tags']; }

    protected function applicaFiltri(Builder $q, array $f): void
    {
        $this->filtroArea($q, $f);
        $this->testo($q, $f, ['title', 'excerpt', 'content']);
        if (!empty($f['visibilita'])) $q->where('visibility', $f['visibilita']);
    }

    protected function ordina(Builder $q): void { $q->orderByRaw('published_at is null desc')->orderByDesc('published_at')->orderByDesc('updated_at'); }

    public function url(Model $m): ?string { return route('blog.edit', $m->id); }

    public function riga(Model $p): array
    {
        return [
            'titolo'       => $p->title,
            'visibilita'   => $p->visibility,
            'pubblicato_il'=> $this->data($p->published_at),
            'area'         => $this->area($p->area),
            'sommario'     => $this->tronca($p->excerpt),
        ];
    }

    public function dettaglio(Model $p): array
    {
        return [
            'titolo'        => $p->title,
            'slug'          => $p->slug,
            'visibilita'    => $p->visibility,
            'pubblicato_il' => $this->data($p->published_at),
            'area'          => $this->area($p->area),
            'voci_di_origine' => $p->entries->map(fn ($e) => ['id' => $e->id, 'data' => $this->data($e->entry_date), 'titolo' => $e->title])->all(),
            'tag'           => $p->tags->pluck('name')->all(),
            'sommario'      => $p->excerpt,
            'testo'         => $p->content,
        ];
    }
}
