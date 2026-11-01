<?php

namespace App\Mcp\Tools;

use App\Actions\TagActions;
use App\Exceptions\RegolaDominio;
use App\Models\Tag;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('modifica_tag')]
#[Description('Rinomina o ricolora un tag; vale ovunque il tag è applicato. Un nome già usato da un altro tag è un errore.')]
#[IsIdempotent]
class ModificaTag extends StrumentoScrittura
{
    protected function esegui(Request $request): Response
    {
        $request->validate(['id' => 'required|integer']);
        $tag  = Tag::find($request->get('id')) ?? throw new RegolaDominio("Nessun tag con id {$request->get('id')}.");
        $dati = $this->campi($request, ['nome' => 'name', 'colore' => 'color']);
        if (!$dati) {
            throw new RegolaDominio('Nessun campo da modificare.');
        }

        app(TagActions::class)->modifica($request->user(), $tag, $dati);

        return $this->risposta('tag', $tag->id, 'modificato');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id'     => $schema->integer()->required(),
            'nome'   => $schema->string(),
            'colore' => $schema->string()->description('#RRGGBB.'),
        ];
    }
}
