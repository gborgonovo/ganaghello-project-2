<?php

namespace App\Actions;

use App\Models\Area;
use App\Models\Entry;
use App\Models\Expense;
use App\Models\Goal;
use App\Models\Inspiration;
use App\Models\Note;
use App\Models\Post;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskUpdate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Nome di dominio e etichetta leggibile di un model, per il registro delle scritture. */
class Entita
{
    private const NOMI = [
        Task::class        => 'task',
        TaskUpdate::class  => 'aggiornamento',
        Goal::class        => 'goal',
        Area::class        => 'area',
        Note::class        => 'nota',
        Tag::class         => 'tag',
        Expense::class     => 'spesa',
        Entry::class       => 'voce',
        Post::class        => 'post',
        Inspiration::class => 'ispirazione',
    ];

    public static function di(Model $m): string
    {
        return self::NOMI[$m::class] ?? Str::snake(class_basename($m));
    }

    public static function etichetta(Model $m): string
    {
        $testo = $m->title ?? $m->name ?? $m->description ?? $m->content ?? "#{$m->getKey()}";

        return self::di($m) . ' «' . Str::limit((string) $testo, 60) . '»';
    }
}
