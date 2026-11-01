<?php

namespace App\Mcp\Tools;

use App\Actions\SpesaActions;
use App\Exceptions\RegolaDominio;
use App\Models\Expense;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('modifica_spesa')]
#[Description('Modifica una spesa. Cambiano solo i campi passati; null svuota un campo facoltativo.')]
#[IsIdempotent]
class ModificaSpesa extends StrumentoScrittura
{
    use CampiSpesa;

    protected function esegui(Request $request): Response
    {
        $request->validate(['id' => 'required|integer']);
        $e = Expense::find($request->get('id')) ?? throw new RegolaDominio("Nessuna spesa con id {$request->get('id')}.");
        $dati = $this->campi($request, $this->mappaSpesa());
        if (!$dati) {
            throw new RegolaDominio('Nessun campo da modificare.');
        }

        app(SpesaActions::class)->modifica($request->user(), $e, $dati);

        return $this->risposta('spesa', $e->id, 'modificata');
    }

    public function schema(JsonSchema $schema): array
    {
        return ['id' => $schema->integer()->required(), ...$this->schemaSpesa($schema)];
    }
}
