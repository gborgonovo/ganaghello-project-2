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

#[Name('assegna_task')]
#[Description('Imposta il responsabile e/o i collaboratori di un task (id utente da `riferimenti`). Un parametro non passato resta com\'è; responsabile_id null toglie il responsabile; collaboratori_ids sostituisce l\'elenco.')]
#[IsIdempotent]
class AssegnaTask extends StrumentoScrittura
{
    protected function esegui(Request $request): Response
    {
        $request->validate(['id' => 'required|integer']);
        $task = Task::find($request->get('id')) ?? throw new RegolaDominio("Nessun task con id {$request->get('id')}.");
        $arg  = $request->all();

        if (!array_key_exists('responsabile_id', $arg) && !array_key_exists('collaboratori_ids', $arg)) {
            throw new RegolaDominio('Passa responsabile_id, collaboratori_ids o entrambi.');
        }

        app(TaskActions::class)->assegna(
            $request->user(), $task,
            $arg['responsabile_id'] ?? null,
            $arg['collaboratori_ids'] ?? null,
            cambiaResponsabile: array_key_exists('responsabile_id', $arg),
        );

        return $this->risposta('task', $task->id, 'assegnazione aggiornata');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id'                => $schema->integer()->required(),
            'responsabile_id'   => $schema->integer(),
            'collaboratori_ids' => $schema->array()->items($schema->integer()),
        ];
    }
}
