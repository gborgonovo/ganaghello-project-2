<?php

namespace App\Mcp\Tools;

use App\Queries\AlberoAree;
use App\Queries\Ripresa;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('briefing')]
#[Description('"Dove ero rimasto": task in corso e in attesa, scadenze dei prossimi 7 giorni e scaduti, ultime voci di diario e, senza filtro di area, i fili dormienti da Mnemosyne. È il punto di partenza per riprendere il lavoro. Con area_id si limita a un\'area e alle sue sotto-aree.')]
#[IsReadOnly]
class Briefing extends Tool
{
    public function handle(Request $request): Response
    {
        $dati = $request->validate(['area_id' => 'nullable|integer|exists:areas,id']);

        $aree = isset($dati['area_id']) ? AlberoAree::conDiscendenti((int) $dati['area_id']) : null;

        return Response::json(['oggi' => today()->toDateString()] + app(Ripresa::class)->quadro($aree));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'area_id' => $schema->integer()->description('Limita il quadro a un\'area e alle sue sotto-aree.'),
        ];
    }
}
