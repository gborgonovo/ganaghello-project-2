<?php

namespace App\Mcp\Tools;

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
        $d = $request->validate(['entita' => 'required|in:' . implode(',', Eliminabili::nomi()), 'id' => 'required|integer']);

        $elemento = Eliminabili::trova($d['entita'], (int) $d['id'], conEliminati: true);
        app(Eliminabili::actions($d['entita']))->ripristina($request->user(), $elemento);

        return $this->risposta($d['entita'], $elemento->id, 'ripristinato');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'entita' => $schema->string()->enum(Eliminabili::nomi())->required(),
            'id'     => $schema->integer()->required(),
        ];
    }
}
