<?php

namespace App\Queries\Entita;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SpesaEntita extends Entita
{
    public const CATEGORIE = ['materiali', 'manodopera', 'professionisti', 'oneri', 'altro'];

    protected function model(): string { return Expense::class; }

    public function filtri(): array { return ['task_id', 'area_id', 'includi_sottoaree', 'categoria', 'fornitore', 'dal', 'al']; }

    protected function relazioniElenco(): array { return ['task']; }

    protected function applicaFiltri(Builder $q, array $f): void
    {
        if (!empty($f['task_id']))   $q->where('task_id', $f['task_id']);
        if (!empty($f['categoria'])) $q->where('category', $f['categoria']);
        if (!empty($f['fornitore'])) $q->where('supplier', 'like', '%' . trim($f['fornitore']) . '%');
        if (!empty($f['area_id'])) {
            $ids = $this->areaIds((int) $f['area_id'], (bool) ($f['includi_sottoaree'] ?? false));
            $q->whereHas('task', fn ($t) => $t->whereIn('area_id', $ids));
        }
        $this->filtroDate($q, $f, 'expense_date');
    }

    protected function ordina(Builder $q): void { $q->orderByDesc('expense_date')->orderByDesc('id'); }

    protected function extraElenco(Builder $q): ?array
    {
        return ['somma_importi' => round((float) $q->sum('amount'), 2)];
    }

    public function url(Model $m): ?string { return route('tasks.show', $m->task_id); }

    public function riga(Model $e): array
    {
        return [
            'data'        => $this->data($e->expense_date),
            'descrizione' => $e->description,
            'importo'     => $e->amount,
            'categoria'   => $e->category,
            'fornitore'   => $e->supplier,
            'task'        => ['id' => $e->task_id, 'titolo' => $e->task?->title],
        ];
    }

    public function dettaglio(Model $e): array
    {
        return $this->riga($e) + ['note' => $e->notes];
    }
}
