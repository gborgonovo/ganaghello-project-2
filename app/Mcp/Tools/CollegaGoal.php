<?php

namespace App\Mcp\Tools;

use App\Actions\TaskActions;
use App\Exceptions\RegolaDominio;
use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('collega_goal')]
#[Description('Aggancia o sgancia un task dai goal a cui contribuisce (id goal da `riferimenti`).')]
#[IsIdempotent]
class CollegaGoal extends StrumentoScrittura
{
    protected function esegui(Request $request): Response
    {
        $d = $request->validate(['id' => 'required|integer', 'aggiungi' => 'nullable|array', 'togli' => 'nullable|array']);
        $task = Task::find($d['id']) ?? throw new RegolaDominio("Nessun task con id {$d['id']}.");

        app(TaskActions::class)->collegaGoal($request->user(), $task, $d['aggiungi'] ?? [], $d['togli'] ?? []);

        return $this->risposta('task', $task->id, 'goal aggiornati');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id'       => $schema->integer()->required(),
            'aggiungi' => $schema->array()->items($schema->integer())->description('Id dei goal da collegare.'),
            'togli'    => $schema->array()->items($schema->integer())->description('Id dei goal da scollegare.'),
        ];
    }
}
