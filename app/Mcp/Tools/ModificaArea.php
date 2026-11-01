<?php

namespace App\Mcp\Tools;

use App\Actions\AreaActions;
use App\Exceptions\RegolaDominio;
use App\Models\Area;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('modifica_area')]
#[Description('Modifica un\'area: nome, descrizione, colori, stato, area padre. Cambiano solo i campi passati.')]
#[IsIdempotent]
class ModificaArea extends StrumentoScrittura
{
    protected function esegui(Request $request): Response
    {
        $request->validate(['id' => 'required|integer']);
        $area = Area::find($request->get('id')) ?? throw new RegolaDominio("Nessuna area con id {$request->get('id')}.");
        $dati = $this->campi($request, ['nome' => 'name', 'descrizione' => 'description', 'colore' => 'color', 'colore_testo' => 'text_color', 'stato' => 'status', 'padre_id' => 'parent_area_id']);
        if (!$dati) {
            throw new RegolaDominio('Nessun campo da modificare.');
        }

        app(AreaActions::class)->modifica($request->user(), $area, $dati);

        return $this->risposta('area', $area->id, 'modificata');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id'           => $schema->integer()->required(),
            'nome'         => $schema->string(),
            'descrizione'  => $schema->string(),
            'colore'       => $schema->string()->description('#RRGGBB.'),
            'colore_testo' => $schema->string()->description('#RRGGBB.'),
            'stato'        => $schema->string()->description('Stato libero dell\'area (per esempio «cantiere»).'),
            'padre_id'     => $schema->integer()->description('Id dell\'area che la contiene.'),
        ];
    }
}
