<?php

namespace App\Livewire;

use App\Queries\RicercaGlobale;
use Livewire\Component;

class GlobalSearch extends Component
{
    public bool   $isOpen = false;
    public string $query  = '';

    public function openSearch(): void
    {
        $this->isOpen = true;
        $this->query  = '';
    }

    public function closeSearch(): void
    {
        $this->isOpen = false;
        $this->query  = '';
    }

    public function render()
    {
        $results = [];

        $trovati      = app(RicercaGlobale::class)->cerca($this->query);
        $tasks        = $trovati['task'] ?? collect();
        $entries      = $trovati['voce'] ?? collect();
        $notes        = $trovati['nota'] ?? collect();
        $inspirations = $trovati['ispirazione'] ?? collect();
        $posts        = $trovati['post'] ?? collect();

        if ($tasks->isNotEmpty()) {
            $results['Task'] = $tasks->map(fn($t) => [
                'label' => $t->title,
                'sub'   => $t->stage?->label,
                'url'   => route('tasks.show', $t->id),
                'icon'  => '⬡',
            ])->all();
        }
        if ($entries->isNotEmpty()) {
            $results['Diario'] = $entries->map(fn($e) => [
                'label' => str($e->content)->limit(70)->toString(),
                'sub'   => $e->entry_date->isoFormat('D MMM YYYY'),
                'url'   => route('diario.show', $e->id),
                'icon'  => '◈',
            ])->all();
        }
        if ($notes->isNotEmpty()) {
            $results['Note'] = $notes->map(fn($n) => [
                'label' => $n->title ?: str($n->content ?? '')->limit(70)->toString(),
                'sub'   => null,
                'url'   => route('note.show', $n->id),
                'icon'  => '◇',
            ])->all();
        }
        if ($inspirations->isNotEmpty()) {
            $results['Moodboard'] = $inspirations->map(fn($i) => [
                'label' => $i->title ?: str($i->description ?? '')->limit(70)->toString(),
                'sub'   => null,
                'url'   => route('moodboard'),
                'icon'  => '❐',
            ])->all();
        }
        if ($posts->isNotEmpty()) {
            $results['Post'] = $posts->map(fn($p) => [
                'label' => $p->title,
                'sub'   => $p->visibility,
                'url'   => null,
                'icon'  => '✦',
            ])->all();
        }

        return view('livewire.global-search', compact('results'));
    }
}
