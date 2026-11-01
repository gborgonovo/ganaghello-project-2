<?php

namespace App\Mcp\Tools;

use App\Actions\NotaActions;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('crea_nota')]
#[Description('Crea una nota, un appunto al volo (misure, numeri, idee da non perdere). Per raccontare una giornata usa una voce di diario; per una cosa da fare, un task.')]
class CreaNota extends StrumentoScrittura
{
    protected function esegui(Request $request): Response
    {
        $el = app(NotaActions::class)->crea($request->user(), $this->campi($request, ['titolo' => 'title', 'testo' => 'content', 'area_id' => 'area_id']));

        return $this->risposta('nota', $el->id, 'creata');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'titolo'  => $schema->string(),
            'testo'   => $schema->string()->required(),
            'area_id' => $schema->integer(),
        ];
    }
}
