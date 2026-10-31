<?php

namespace App\Queries\Entita;

use App\Models\Inspiration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class IspirazioneEntita extends Entita
{
    protected function model(): string { return Inspiration::class; }

    public function filtri(): array { return ['area_id', 'includi_sottoaree', 'testo']; }

    protected function relazioniElenco(): array { return ['area']; }

    protected function relazioniDettaglio(): array { return ['area', 'tags']; }

    protected function applicaFiltri(Builder $q, array $f): void
    {
        $this->filtroArea($q, $f);
        $this->testo($q, $f, ['title', 'description']);
    }

    protected function ordina(Builder $q): void { $q->orderByDesc('id'); }

    public function url(Model $m): ?string { return route('moodboard.show', $m->id); }

    public function riga(Model $i): array
    {
        return ['titolo' => $i->title, 'area' => $this->area($i->area), 'link' => $i->url, 'descrizione' => $this->tronca($i->description)];
    }

    public function dettaglio(Model $i): array
    {
        return ['titolo' => $i->title, 'area' => $this->area($i->area), 'link' => $i->url, 'tag' => $i->tags->pluck('name')->all(), 'descrizione' => $i->description];
    }
}
