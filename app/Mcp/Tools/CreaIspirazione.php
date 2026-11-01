<?php

namespace App\Mcp\Tools;

use App\Actions\IspirazioneActions;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('crea_ispirazione')]
#[Description('Aggiunge un\'ispirazione alla moodboard: un\'idea, un riferimento, un link visto in giro, eventualmente legato a un\'area. Serve almeno un titolo, una descrizione o un link. Le immagini si aggiungono dall\'app.')]
class CreaIspirazione extends StrumentoScrittura
{
    use CampiIspirazione;

    protected function esegui(Request $request): Response
    {
        $insp = app(IspirazioneActions::class)->crea($request->user(), $this->campi($request, $this->mappaIspirazione()));

        return $this->risposta('ispirazione', $insp->id, 'creata');
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->schemaIspirazione($schema);
    }
}
