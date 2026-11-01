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

#[Name('modifica_task')]
#[Description('Modifica i campi di un task. Cambiano solo i campi passati; null svuota un campo. Lo stage non si cambia qui ma con `sposta_stage`; assegnazione, goal, voci e tag hanno i loro tool.')]
#[IsIdempotent]
class ModificaTask extends StrumentoScrittura
{
    use CampiTask;

    protected function esegui(Request $request): Response
    {
        $request->validate(['id' => 'required|integer']);
        $task = Task::find($request->get('id')) ?? throw new RegolaDominio("Nessun task con id {$request->get('id')}.");

        $dati = $this->campi($request, $this->mappaTask() + ['chiuso_il' => 'completed_at']);
        if (!$dati) {
            throw new RegolaDominio('Nessun campo da modificare.');
        }

        app(TaskActions::class)->modifica($request->user(), $task, $dati);

        return $this->risposta('task', $task->id, 'modificato');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required(),
            ...$this->schemaTask($schema),
            'chiuso_il' => $schema->string()->description('AAAA-MM-GG, data di chiusura di un task fatto.'),
        ];
    }
}
