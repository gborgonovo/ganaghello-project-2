<?php

namespace App\Queries\Entita;

use App\Models\Entry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class VoceEntita extends Entita
{
    protected function model(): string { return Entry::class; }

    public function filtri(): array { return ['area_id', 'includi_sottoaree', 'task_id', 'tipo', 'dal', 'al', 'testo']; }

    protected function relazioniElenco(): array { return ['area']; }

    protected function relazioniDettaglio(): array { return ['area', 'tasks', 'posts', 'tags']; }

    protected function applicaFiltri(Builder $q, array $f): void
    {
        $this->filtroArea($q, $f);
        $this->testo($q, $f, ['title', 'content']);
        $this->filtroDate($q, $f, 'entry_date');
        if (!empty($f['tipo']))    $q->where('kind', $f['tipo']);
        if (!empty($f['task_id'])) $q->whereHas('tasks', fn ($t) => $t->where('tasks.id', $f['task_id']));
        $q->withCount('attachments');
    }

    protected function ordina(Builder $q): void { $q->orderByDesc('entry_date')->orderByDesc('entry_time')->orderByDesc('id'); }

    public function url(Model $m): ?string { return route('diario.show', $m->id); }

    public function riga(Model $e): array
    {
        return [
            'data'    => $this->data($e->entry_date),
            'titolo'  => $e->title,
            'tipo'    => $e->kind,
            'area'    => $this->area($e->area),
            'con_foto'=> ($e->attachments_count ?? $e->attachments()->count()) > 0,
            'testo'   => $this->tronca($e->content),
        ];
    }

    public function dettaglio(Model $e): array
    {
        return [
            'data'     => $this->data($e->entry_date),
            'ora'      => $e->entry_time ? substr((string) $e->entry_time, 0, 5) : null,
            'titolo'   => $e->title,
            'tipo'     => $e->kind,
            'area'     => $this->area($e->area),
            'con_foto' => $e->attachments()->exists(),
            'task'     => $e->tasks->map(fn ($t) => ['id' => $t->id, 'titolo' => $t->title])->all(),
            'post'     => $e->posts->map(fn ($p) => ['id' => $p->id, 'titolo' => $p->title, 'visibilita' => $p->visibility])->all(),
            'tag'      => $e->tags->pluck('name')->all(),
            'testo'    => $e->content,
        ];
    }
}
