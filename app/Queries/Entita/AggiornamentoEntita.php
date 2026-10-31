<?php

namespace App\Queries\Entita;

use App\Exceptions\RegolaDominio;
use App\Models\TaskUpdate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AggiornamentoEntita extends Entita
{
    protected function model(): string { return TaskUpdate::class; }

    public function filtri(): array { return ['task_id']; }

    protected function relazioniElenco(): array { return ['task']; }

    protected function applicaFiltri(Builder $q, array $f): void
    {
        if (empty($f['task_id'])) {
            throw new RegolaDominio('Per elencare gli aggiornamenti serve il filtro task_id.');
        }
        $q->where('task_id', $f['task_id']);
    }

    protected function ordina(Builder $q): void { $q->latest()->orderByDesc('id'); }

    public function url(Model $m): ?string { return route('tasks.show', $m->task_id); }

    public function riga(Model $u): array
    {
        return ['task_id' => $u->task_id, 'data' => $this->data($u->created_at), 'testo' => $this->tronca($u->content)];
    }

    public function dettaglio(Model $u): array
    {
        return [
            'task'        => ['id' => $u->task_id, 'titolo' => $u->task?->title],
            'data'        => $this->data($u->created_at),
            'modificato'  => $u->updated_at && $u->created_at && !$u->updated_at->equalTo($u->created_at),
            'testo'       => $u->content,
        ];
    }
}
