<?php

namespace App\Mcp\Tools;

use App\Actions\AreaActions;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('crea_area')]
#[Description('Crea un\'area, cioè un luogo della casa o del terreno, eventualmente dentro un\'altra (padre_id). Uso raro: prima controlla con `riferimenti` che non esista già.')]
class CreaArea extends StrumentoScrittura
{
    protected function esegui(Request $request): Response
    {
        $area = app(AreaActions::class)->crea($request->user(), $this->campi($request, ['nome' => 'name', 'descrizione' => 'description', 'colore' => 'color', 'colore_testo' => 'text_color', 'stato' => 'status', 'padre_id' => 'parent_area_id']));

        return $this->risposta('area', $area->id, 'creata');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'nome'         => $schema->string()->required(),
            'descrizione'  => $schema->string(),
            'colore'       => $schema->string()->description('#RRGGBB.'),
            'colore_testo' => $schema->string()->description('#RRGGBB.'),
            'stato'        => $schema->string()->description('Stato libero dell\'area (per esempio «cantiere»).'),
            'padre_id'     => $schema->integer()->description('Id dell\'area che la contiene.'),
        ];
    }
}
