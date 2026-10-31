<?php

namespace App\Queries\Entita;

use App\Models\Goal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GoalEntita extends Entita
{
    protected function model(): string { return Goal::class; }

    public function filtri(): array { return ['stato', 'padre_id', 'testo']; }

    protected function relazioniDettaglio(): array { return ['parent', 'children', 'tasks.stage']; }

    protected function applicaFiltri(Builder $q, array $f): void
    {
        $this->testo($q, $f, ['title', 'description']);
        if (!empty($f['stato']))    $q->where('status', $f['stato']);
        if (!empty($f['padre_id'])) $q->where('parent_goal_id', $f['padre_id']);
    }

    protected function ordina(Builder $q): void { $q->orderBy('title'); }

    public function url(Model $m): ?string { return route('goals'); }

    public function riga(Model $g): array
    {
        return ['titolo' => $g->title, 'stato' => $g->status, 'scadenza' => $this->data($g->deadline), 'padre_id' => $g->parent_goal_id, 'descrizione' => $this->tronca($g->description)];
    }

    public function dettaglio(Model $g): array
    {
        return [
            'titolo'      => $g->title,
            'descrizione' => $g->description,
            'stato'       => $g->status,
            'scadenza'    => $this->data($g->deadline),
            'padre'       => $g->parent ? ['id' => $g->parent->id, 'titolo' => $g->parent->title] : null,
            'sotto_goal'  => $g->children->map(fn ($c) => ['id' => $c->id, 'titolo' => $c->title, 'stato' => $c->status])->all(),
            'task'        => $g->tasks->map(fn ($t) => ['id' => $t->id, 'titolo' => $t->title, 'stage' => $t->stage?->code])->all(),
        ];
    }
}
