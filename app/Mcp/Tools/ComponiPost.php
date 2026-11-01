<?php

namespace App\Mcp\Tools;

use App\Actions\PostActions;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('componi_post')]
#[Description(<<<'TXT'
Compone un post del blog dalle voci di diario scelte: nasce sempre in bozza, con le foto delle voci e il collegamento a esse. Le voci restano intatte. Senza `testo` il post contiene i testi delle voci in ordine cronologico; se l'utente ti chiede di metterli in forma, usa le sue parole e le sue voci, senza inventare fatti o emozioni che non ha raccontato, e mostragli titolo e testo prima di salvare. Il post non diventa mai pubblico da qui: la pubblicazione la fa l'utente dall'app (dagli il link).
TXT)]
class ComponiPost extends StrumentoScrittura
{
    protected function esegui(Request $request): Response
    {
        $d = $request->validate(['voce_ids' => 'required|array|min:1', 'voce_ids.*' => 'integer', 'titolo' => 'nullable|string|max:255', 'testo' => 'nullable|string']);

        $post = app(PostActions::class)->componi($request->user(), $d['voce_ids'], $d['titolo'] ?? null, $d['testo'] ?? null);

        return $this->risposta('post', $post->id, 'composto in bozza');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'voce_ids' => $schema->array()->items($schema->integer())->description('Id delle voci di diario da cui comporre.')->required(),
            'titolo'   => $schema->string(),
            'testo'    => $schema->string()->description('Facoltativo: il testo del post approvato dall\'utente.'),
        ];
    }
}
