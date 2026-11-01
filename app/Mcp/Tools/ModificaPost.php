<?php

namespace App\Mcp\Tools;

use App\Actions\PostActions;
use App\Exceptions\RegolaDominio;
use App\Models\Post;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('modifica_post')]
#[Description('Modifica un post in bozza o privato: titolo, sommario, testo, area, visibilità tra draft e private. Cambiano solo i campi passati. Un post pubblicato non si tocca da qui, e nessun post diventa pubblico da qui: per entrambe le cose l\'utente usa l\'app.')]
#[IsIdempotent]
class ModificaPost extends StrumentoScrittura
{
    protected function esegui(Request $request): Response
    {
        $request->validate(['id' => 'required|integer', 'visibilita' => 'nullable|in:draft,private']);
        $post = Post::find($request->get('id')) ?? throw new RegolaDominio("Nessun post con id {$request->get('id')}.");
        if ($post->isPublic()) {
            throw new RegolaDominio('Il post è pubblicato: modificalo dall\'app.');
        }
        $dati = $this->campi($request, ['titolo' => 'title', 'sommario' => 'excerpt', 'testo' => 'content', 'area_id' => 'area_id', 'visibilita' => 'visibility']);
        if (!$dati) {
            throw new RegolaDominio('Nessun campo da modificare.');
        }

        app(PostActions::class)->modifica($request->user(), $post, $dati);

        return $this->risposta('post', $post->id, 'modificato');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id'         => $schema->integer()->required(),
            'titolo'     => $schema->string(),
            'sommario'   => $schema->string()->description('Massimo 500 caratteri.'),
            'testo'      => $schema->string(),
            'area_id'    => $schema->integer(),
            'visibilita' => $schema->string()->enum(['draft', 'private']),
        ];
    }
}
