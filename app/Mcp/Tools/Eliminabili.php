<?php

namespace App\Mcp\Tools;

use App\Actions\AggiornamentoActions;
use App\Actions\AreaActions;
use App\Actions\GoalActions;
use App\Actions\IspirazioneActions;
use App\Actions\NotaActions;
use App\Actions\PostActions;
use App\Actions\SpesaActions;
use App\Actions\TagActions;
use App\Actions\TaskActions;
use App\Actions\VoceActions;
use App\Exceptions\RegolaDominio;
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

/** Le entità che `elimina` e `ripristina` sanno trattare: model e action di ciascuna. */
class Eliminabili
{
    private const MAPPA = [
        'task'          => [Task::class, TaskActions::class],
        'aggiornamento' => [TaskUpdate::class, AggiornamentoActions::class],
        'spesa'         => [Expense::class, SpesaActions::class],
        'voce'          => [Entry::class, VoceActions::class],
        'post'          => [Post::class, PostActions::class],
        'goal'          => [Goal::class, GoalActions::class],
        'nota'          => [Note::class, NotaActions::class],
        'area'          => [Area::class, AreaActions::class],
        'tag'           => [Tag::class, TagActions::class],
        'ispirazione'   => [Inspiration::class, IspirazioneActions::class],
    ];

    public static function nomi(): array
    {
        return array_keys(self::MAPPA);
    }

    public static function actions(string $entita): string
    {
        return self::MAPPA[$entita][1];
    }

    public static function trova(string $entita, int $id, bool $conEliminati = false): Model
    {
        $model = self::MAPPA[$entita][0];
        $q     = $conEliminati ? $model::withTrashed() : $model::query();

        return $q->find($id) ?? throw new RegolaDominio("Nessun elemento di tipo {$entita} con id {$id}.");
    }
}
