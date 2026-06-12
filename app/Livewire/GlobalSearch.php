<?php

namespace App\Livewire;

use App\Models\Entry;
use App\Models\Inspiration;
use App\Models\Note;
use App\Models\Post;
use App\Models\Task;
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

        if (strlen(trim($this->query)) >= 2) {
            $term = '%' . trim($this->query) . '%';

            $tasks = Task::where(fn($q) => $q->where('title', 'like', $term)->orWhere('description', 'like', $term))
                ->with('stage')
                ->orderByDesc('updated_at')
                ->limit(5)->get();

            $entries = Entry::where('content', 'like', $term)
                ->orderByDesc('entry_date')
                ->limit(4)->get();

            $notes = Note::where(fn($q) => $q->where('title', 'like', $term)->orWhere('content', 'like', $term))
                ->orderByDesc('updated_at')
                ->limit(4)->get();

            $inspirations = Inspiration::where(fn($q) => $q->where('title', 'like', $term)->orWhere('description', 'like', $term))
                ->orderByDesc('id')
                ->limit(4)->get();

            $posts = Post::where(fn($q) => $q->where('title', 'like', $term)
                    ->orWhere('excerpt', 'like', $term)
                    ->orWhere('content', 'like', $term))
                ->orderByDesc('published_at')
                ->limit(4)->get();

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
        }

        return view('livewire.global-search', compact('results'));
    }
}
