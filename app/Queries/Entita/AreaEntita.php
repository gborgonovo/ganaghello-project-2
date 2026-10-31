<?php

namespace App\Queries\Entita;

use App\Models\Area;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AreaEntita extends Entita
{
    protected function model(): string { return Area::class; }

    public function filtri(): array { return ['padre_id', 'testo']; }

    protected function relazioniDettaglio(): array { return ['parent', 'children']; }

    protected function applicaFiltri(Builder $q, array $f): void
    {
        $this->testo($q, $f, ['name', 'description']);
        if (!empty($f['padre_id'])) $q->where('parent_area_id', $f['padre_id']);
    }

    protected function ordina(Builder $q): void { $q->orderBy('sequence')->orderBy('name'); }

    public function url(Model $m): ?string { return route('aree.show', $m->id); }

    public function riga(Model $a): array
    {
        return ['nome' => $a->name, 'stato' => $a->status, 'padre_id' => $a->parent_area_id, 'descrizione' => $this->tronca($a->description)];
    }

    public function dettaglio(Model $a): array
    {
        return [
            'nome'        => $a->name,
            'descrizione' => $a->description,
            'stato'       => $a->status,
            'padre'       => $this->area($a->parent),
            'sotto_aree'  => $a->children->map(fn ($c) => $this->area($c))->all(),
            'task_aperti' => $a->tasks()->whereHas('stage', fn ($s) => $s->whereNotIn('code', TaskEntita::CHIUSI))->count(),
            'voci'        => $a->entries()->count(),
            'note'        => $a->notes()->count(),
        ];
    }
}
