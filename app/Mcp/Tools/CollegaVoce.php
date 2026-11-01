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

#[Name('collega_voce')]
#[Description('Collega o scollega voci di diario a un task: la voce racconta un momento di quel lavoro. Gli id delle voci si trovano con `elenca` (entita voce) o `cerca`.')]
#[IsIdempotent]
class CollegaVoce extends StrumentoScrittura
{
    protected function esegui(Request $request): Response
    {
        $d = $request->validate(['task_id' => 'required|integer', 'aggiungi' => 'nullable|array', 'togli' => 'nullable|array']);
        $task = Task::find($d['task_id']) ?? throw new RegolaDominio("Nessun task con id {$d['task_id']}.");

        app(TaskActions::class)->collegaVoci($request->user(), $task, $d['aggiungi'] ?? [], $d['togli'] ?? []);

        return $this->risposta('task', $task->id, 'voci collegate aggiornate');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id'  => $schema->integer()->required(),
            'aggiungi' => $schema->array()->items($schema->integer())->description('Id delle voci da collegare.'),
            'togli'    => $schema->array()->items($schema->integer())->description('Id delle voci da scollegare.'),
        ];
    }
}
