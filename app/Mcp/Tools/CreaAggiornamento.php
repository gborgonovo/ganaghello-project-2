<?php

namespace App\Mcp\Tools;

use App\Actions\AggiornamentoActions;
use App\Exceptions\RegolaDominio;
use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('crea_aggiornamento')]
#[Description('Aggiunge a un task un aggiornamento datato oggi: una riga di cronaca operativa ("arrivate le tegole", "il geometra ha chiamato"). Scrivi con le parole dell\'utente. Per raccontare una giornata o un momento usa invece una voce di diario.')]
class CreaAggiornamento extends StrumentoScrittura
{
    protected function esegui(Request $request): Response
    {
        $d = $request->validate(['task_id' => 'required|integer', 'testo' => 'required|string']);
        $task = Task::find($d['task_id']) ?? throw new RegolaDominio("Nessun task con id {$d['task_id']}.");

        $u = app(AggiornamentoActions::class)->crea($request->user(), $task, $d['testo']);

        return $this->risposta('aggiornamento', $u->id, 'creato');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()->required(),
            'testo'   => $schema->string()->required(),
        ];
    }
}
