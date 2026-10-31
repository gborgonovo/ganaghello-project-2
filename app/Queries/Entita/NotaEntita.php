<?php

namespace App\Queries\Entita;

use App\Models\Note;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NotaEntita extends Entita
{
    protected function model(): string { return Note::class; }

    public function filtri(): array { return ['area_id', 'includi_sottoaree', 'testo']; }

    protected function relazioniElenco(): array { return ['area']; }

    protected function relazioniDettaglio(): array { return ['area', 'tags']; }

    protected function applicaFiltri(Builder $q, array $f): void
    {
        $this->filtroArea($q, $f);
        $this->testo($q, $f, ['title', 'content']);
    }

    protected function ordina(Builder $q): void { $q->orderByDesc('updated_at'); }

    public function url(Model $m): ?string { return route('note.show', $m->id); }

    public function riga(Model $n): array
    {
        return ['titolo' => $n->title, 'area' => $this->area($n->area), 'aggiornata_il' => $this->data($n->updated_at), 'testo' => $this->tronca($n->content)];
    }

    public function dettaglio(Model $n): array
    {
        return ['titolo' => $n->title, 'area' => $this->area($n->area), 'aggiornata_il' => $this->data($n->updated_at), 'tag' => $n->tags->pluck('name')->all(), 'testo' => $n->content];
    }
}
