<?php

namespace App\Mcp\Tools;

use App\Actions\AggiornamentoActions;
use App\Exceptions\RegolaDominio;
use App\Models\TaskUpdate;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('modifica_aggiornamento')]
#[Description('Corregge il testo di un aggiornamento di task (gli id si trovano con `leggi` sul task o `elenca` con entita aggiornamento).')]
#[IsIdempotent]
class ModificaAggiornamento extends StrumentoScrittura
{
    protected function esegui(Request $request): Response
    {
        $d = $request->validate(['id' => 'required|integer', 'testo' => 'required|string']);
        $u = TaskUpdate::find($d['id']) ?? throw new RegolaDominio("Nessun aggiornamento con id {$d['id']}.");

        app(AggiornamentoActions::class)->modifica($request->user(), $u, $d['testo']);

        return $this->risposta('aggiornamento', $u->id, 'modificato');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id'    => $schema->integer()->required(),
            'testo' => $schema->string()->required(),
        ];
    }
}
