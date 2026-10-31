<?php

namespace App\Queries\Entita;

use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class TaskEntita extends Entita
{
    public const CHIUSI = ['done', 'archiviato'];

    protected function model(): string
    {
        return Task::class;
    }

    public function filtri(): array
    {
        return ['area_id', 'includi_sottoaree', 'stage', 'aperti', 'tag', 'goal_id', 'priorita', 'executor',
                'assegnato_a', 'padre_id', 'scadenza_entro_giorni', 'scaduti', 'testo'];
    }

    protected function relazioniElenco(): array
    {
        return ['stage', 'area', 'assignedUser'];
    }

    protected function relazioniDettaglio(): array
    {
        return ['stage', 'area', 'assignedUser', 'parent', 'children.stage', 'tags', 'goals', 'collaborators',
                'updates' => fn ($q) => $q->latest()->limit(10),
                'entries' => fn ($q) => $q->orderByDesc('entry_date'),
                'expenses' => fn ($q) => $q->orderByDesc('expense_date')];
    }

    protected function applicaFiltri(Builder $q, array $f): void
    {
        $this->filtroArea($q, $f);
        $this->testo($q, $f, ['title', 'description']);

        if (!empty($f['stage'])) {
            $q->whereHas('stage', fn ($s) => $s->whereIn('code', (array) $f['stage']));
        }
        if (!empty($f['aperti'])) {
            $q->whereHas('stage', fn ($s) => $s->whereNotIn('code', self::CHIUSI));
        }
        if (!empty($f['tag'])) {
            $q->whereHas('tags', fn ($t) => $t->where('name', mb_strtolower(trim($f['tag']))));
        }
        if (!empty($f['goal_id'])) {
            $q->whereHas('goals', fn ($g) => $g->where('goals.id', $f['goal_id']));
        }
        foreach (['priorita' => 'priority', 'executor' => 'executor', 'assegnato_a' => 'assigned_to', 'padre_id' => 'parent_task_id'] as $filtro => $colonna) {
            if (!empty($f[$filtro])) {
                $q->where($colonna, $f[$filtro]);
            }
        }
        if (!empty($f['scadenza_entro_giorni']) || !empty($f['scaduti'])) {
            $q->whereNull('completed_at')->whereNotNull('due_date');
            if (!empty($f['scadenza_entro_giorni'])) {
                $q->whereDate('due_date', '<=', Carbon::today()->addDays((int) $f['scadenza_entro_giorni']));
                if (empty($f['scaduti'])) {
                    $q->whereDate('due_date', '>=', Carbon::today());
                }
            } else {
                $q->whereDate('due_date', '<', Carbon::today());
            }
        }
    }

    protected function ordina(Builder $q): void
    {
        $q->orderByRaw('due_date is null')->orderBy('due_date')->orderByDesc('updated_at');
    }

    public function url(Model $m): ?string
    {
        return route('tasks.show', $m->id);
    }

    public function riga(Model $t): array
    {
        return [
            'titolo'      => $t->title,
            'stage'       => $t->stage?->code,
            'area'        => $this->area($t->area),
            'priorita'    => $t->priority,
            'scadenza'    => $this->data($t->due_date),
            'chiuso_il'   => $this->data($t->completed_at),
            'padre_id'    => $t->parent_task_id,
            'responsabile'=> $t->assignedUser?->name,
            'descrizione' => $this->tronca($t->description),
        ];
    }

    public function dettaglio(Model $t): array
    {
        return [
            'titolo'        => $t->title,
            'descrizione'   => $t->description,
            'stage'         => $t->stage?->code,
            'area'          => $this->area($t->area),
            'priorita'      => $t->priority,
            'esecutore'     => $t->executor,
            'stima_ore'     => $t->work_estimate,
            'inizio'        => $this->data($t->start_date),
            'scadenza'      => $this->data($t->due_date),
            'chiuso_il'     => $this->data($t->completed_at),
            'costo_min'     => $t->cost_min,
            'costo_max'     => $t->cost_max,
            'base_costo'    => $t->cost_basis,
            'costo_reale'   => $t->cost_real,
            'padre'         => $t->parent ? ['id' => $t->parent->id, 'titolo' => $t->parent->title] : null,
            'sotto_task'    => $t->children->map(fn ($c) => ['id' => $c->id, 'titolo' => $c->title, 'stage' => $c->stage?->code])->all(),
            'tag'           => $t->tags->pluck('name')->all(),
            'goal'          => $t->goals->map(fn ($g) => ['id' => $g->id, 'titolo' => $g->title])->all(),
            'responsabile'  => $t->assignedUser?->name,
            'collaboratori' => $t->collaborators->pluck('name')->all(),
            'ultimi_aggiornamenti' => $t->updates->map(fn ($u) => ['id' => $u->id, 'data' => $this->data($u->created_at), 'testo' => $u->content])->all(),
            'voci_collegate' => $t->entries->map(fn ($e) => ['id' => $e->id, 'data' => $this->data($e->entry_date), 'titolo' => $e->title])->all(),
            'spese'         => $t->expenses->map(fn ($e) => ['id' => $e->id, 'data' => $this->data($e->expense_date), 'descrizione' => $e->description, 'importo' => $e->amount, 'categoria' => $e->category])->all(),
            'totale_spese'  => round($t->expenses->sum('amount'), 2),
        ];
    }
}
