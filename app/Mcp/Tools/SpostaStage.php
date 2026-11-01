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

#[Name('sposta_stage')]
#[Description('Cambia lo stage di un task. Per todo, doing e in_attesa serve una scadenza (quella del task o `scadenza`); entrando in done o archiviato si imposta la data di chiusura (oggi o `chiuso_il`), che si toglie solo riaprendo il task. Lo stage «archiviato» è uno stato del task, non un\'eliminazione.')]
#[IsIdempotent]
class SpostaStage extends StrumentoScrittura
{
    protected function esegui(Request $request): Response
    {
        $d = $request->validate([
            'id'        => 'required|integer',
            'stage'     => 'required|string',
            'scadenza'  => 'nullable|date_format:Y-m-d',
            'chiuso_il' => 'nullable|date_format:Y-m-d',
        ]);
        $task  = Task::with('stage')->find($d['id']) ?? throw new RegolaDominio("Nessun task con id {$d['id']}.");
        $prima = $task->stage?->code;

        app(TaskActions::class)->spostaStage($request->user(), $task, $d['stage'], $d['scadenza'] ?? null, $d['chiuso_il'] ?? null);

        return $this->risposta('task', $task->id, "spostato da {$prima} a {$d['stage']}");
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id'        => $schema->integer()->required(),
            'stage'     => $schema->string()->enum(['idea', 'discussione', 'approvato', 'todo', 'doing', 'in_attesa', 'done', 'archiviato'])->required(),
            'scadenza'  => $schema->string()->description('AAAA-MM-GG, se il task non ce l\'ha e lo stage la richiede.'),
            'chiuso_il' => $schema->string()->description('AAAA-MM-GG, per done o archiviato se non è oggi.'),
        ];
    }
}
