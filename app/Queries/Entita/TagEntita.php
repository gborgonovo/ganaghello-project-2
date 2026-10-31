<?php

namespace App\Queries\Entita;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TagEntita extends Entita
{
    protected function model(): string { return Tag::class; }

    public function filtri(): array { return ['testo']; }

    protected function applicaFiltri(Builder $q, array $f): void
    {
        $this->testo($q, $f, ['name']);
        $q->withCount(['tasks', 'entries', 'notes', 'posts', 'inspirations']);
    }

    protected function ordina(Builder $q): void { $q->orderBy('name'); }

    public function url(Model $m): ?string { return route('settings.tag'); }

    public function riga(Model $t): array
    {
        return ['nome' => $t->name, 'usi' => ['task' => $t->tasks_count, 'voci' => $t->entries_count, 'note' => $t->notes_count, 'post' => $t->posts_count, 'ispirazioni' => $t->inspirations_count]];
    }

    public function dettaglio(Model $t): array
    {
        $t->loadCount(['tasks', 'entries', 'notes', 'posts', 'inspirations']);

        return $this->riga($t);
    }
}
