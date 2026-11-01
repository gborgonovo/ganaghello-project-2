<?php

namespace App\Mcp\Tools;

use App\Actions\SpesaActions;
use App\Exceptions\RegolaDominio;
use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('crea_spesa')]
#[Description('Registra una spesa su un task: descrizione e importo obbligatori, poi data, categoria, fornitore e note. Se la data non è detta, chiedila o usa quella che l\'utente ha indicato; non inventarla.')]
class CreaSpesa extends StrumentoScrittura
{
    use CampiSpesa;

    protected function esegui(Request $request): Response
    {
        $request->validate(['task_id' => 'required|integer']);
        $task = Task::find($request->get('task_id')) ?? throw new RegolaDominio("Nessun task con id {$request->get('task_id')}.");

        $e = app(SpesaActions::class)->crea($request->user(), $task, $this->campi($request, $this->mappaSpesa()));

        return $this->risposta('spesa', $e->id, 'creata');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()->required(),
            ...$this->schemaSpesa($schema),
            'descrizione' => $schema->string()->required(),
            'importo'     => $schema->number()->description('Euro, almeno 0,01.')->required(),
        ];
    }
}
