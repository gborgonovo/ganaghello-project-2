<?php

namespace App\Mcp\Tools;

use App\Actions\NotaActions;
use App\Exceptions\RegolaDominio;
use App\Models\Note;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('modifica_nota')]
#[Description('Modifica una nota. Cambiano solo i campi passati.')]
#[IsIdempotent]
class ModificaNota extends StrumentoScrittura
{
    protected function esegui(Request $request): Response
    {
        $request->validate(['id' => 'required|integer']);
        $el   = Note::find($request->get('id')) ?? throw new RegolaDominio("Nessun elemento di tipo nota con id {$request->get('id')}.");
        $dati = $this->campi($request, ['titolo' => 'title', 'testo' => 'content', 'area_id' => 'area_id']);
        if (!$dati) {
            throw new RegolaDominio('Nessun campo da modificare.');
        }

        app(NotaActions::class)->modifica($request->user(), $el, $dati);

        return $this->risposta('nota', $el->id, 'modificata');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required(),
            'titolo'  => $schema->string(),
            'testo'   => $schema->string(),
            'area_id' => $schema->integer(),
        ];
    }
}
