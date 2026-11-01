<?php

namespace App\Mcp\Tools;

use App\Actions\AggiornamentoActions;
use App\Actions\SpesaActions;
use App\Actions\TaskActions;
use App\Actions\VoceActions;
use App\Exceptions\RegolaDominio;
use App\Models\Entry;
use App\Models\Expense;
use App\Models\Task;
use App\Models\TaskUpdate;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('ripristina')]
#[Description('Ripristina un elemento eliminato. Gli eliminati si trovano con `elenca` e includi_archiviati.')]
#[IsIdempotent]
class Ripristina extends StrumentoScrittura
{
    protected function esegui(Request $request): Response
    {
        $d  = $request->validate(['entita' => 'required|in:' . implode(',', Elimina::ENTITA), 'id' => 'required|integer']);
        $u  = $request->user();
        $id = (int) $d['id'];

        match ($d['entita']) {
            'task'          => app(TaskActions::class)->ripristina($u, Task::withTrashed()->find($id) ?? throw new RegolaDominio("Nessun task con id {$id}.")),
            'aggiornamento' => app(AggiornamentoActions::class)->ripristina($u, TaskUpdate::withTrashed()->find($id) ?? throw new RegolaDominio("Nessun aggiornamento con id {$id}.")),
            'spesa'         => app(SpesaActions::class)->ripristina($u, Expense::withTrashed()->find($id) ?? throw new RegolaDominio("Nessuna spesa con id {$id}.")),
            'voce'          => app(VoceActions::class)->ripristina($u, Entry::withTrashed()->find($id) ?? throw new RegolaDominio("Nessuna voce con id {$id}.")),
        };

        return $this->risposta($d['entita'], $id, 'ripristinato');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'entita' => $schema->string()->enum(Elimina::ENTITA)->required(),
            'id'     => $schema->integer()->required(),
        ];
    }
}
