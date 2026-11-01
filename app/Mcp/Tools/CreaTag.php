<?php

namespace App\Mcp\Tools;

use App\Actions\TagActions;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('crea_tag')]
#[Description('Crea un tag (sempre minuscolo). Di solito non serve: `applica_tag` crea da sé i tag che mancano. Se esisteva un tag eliminato con lo stesso nome, viene ripristinato.')]
class CreaTag extends StrumentoScrittura
{
    protected function esegui(Request $request): Response
    {
        $tag = app(TagActions::class)->crea($request->user(), $this->campi($request, ['nome' => 'name', 'colore' => 'color']));

        return $this->risposta('tag', $tag->id, 'creato');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'nome'   => $schema->string()->required(),
            'colore' => $schema->string()->description('#RRGGBB.'),
        ];
    }
}
