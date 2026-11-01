<?php

namespace App\Mcp\Tools;

use App\Actions\IspirazioneActions;
use App\Exceptions\RegolaDominio;
use App\Models\Inspiration;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('modifica_ispirazione')]
#[Description('Modifica un\'ispirazione della moodboard. Cambiano solo i campi passati.')]
#[IsIdempotent]
class ModificaIspirazione extends StrumentoScrittura
{
    use CampiIspirazione;

    protected function esegui(Request $request): Response
    {
        $request->validate(['id' => 'required|integer']);
        $insp = Inspiration::find($request->get('id')) ?? throw new RegolaDominio("Nessuna ispirazione con id {$request->get('id')}.");
        $dati = $this->campi($request, $this->mappaIspirazione());
        if (!$dati) {
            throw new RegolaDominio('Nessun campo da modificare.');
        }

        app(IspirazioneActions::class)->modifica($request->user(), $insp, $dati);

        return $this->risposta('ispirazione', $insp->id, 'modificata');
    }

    public function schema(JsonSchema $schema): array
    {
        return ['id' => $schema->integer()->required(), ...$this->schemaIspirazione($schema)];
    }
}
