<?php

namespace App\Mcp\Tools;

use App\Actions\TaskActions;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('crea_task')]
#[Description('Crea un task (o un sotto-task con padre_id). Lo stage predefinito è idea; per todo, doing e in_attesa serve la scadenza. I tag si danno per nome (quelli nuovi vengono creati), goal e utenti per id (da `riferimenti`). Prima di chiamarlo riassumi all\'utente cosa stai per creare.')]
class CreaTask extends StrumentoScrittura
{
    use CampiTask;

    protected function esegui(Request $request): Response
    {
        $dati = $this->campi($request, $this->mappaTask() + [
            'stage'             => 'stage',
            'responsabile_id'   => 'assigned_to',
            'collaboratori_ids' => 'collaboratori',
            'tag'               => 'tag',
            'goal_ids'          => 'goal_ids',
        ]);

        $task = app(TaskActions::class)->crea($request->user(), $dati);

        return $this->risposta('task', $task->id, 'creato');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->schemaTask($schema),
            'titolo'            => $schema->string()->required(),
            'stage'             => $schema->string()->enum(['idea', 'discussione', 'approvato', 'todo', 'doing', 'in_attesa', 'done', 'archiviato']),
            'responsabile_id'   => $schema->integer(),
            'collaboratori_ids' => $schema->array()->items($schema->integer()),
            'tag'               => $schema->array()->items($schema->string()),
            'goal_ids'          => $schema->array()->items($schema->integer()),
        ];
    }
}
