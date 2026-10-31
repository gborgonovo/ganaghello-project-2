<?php

namespace App\Mcp\Tools;

use App\Exceptions\RegolaDominio;
use App\Queries\Entita\Catalogo;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('leggi')]
#[Description('Legge un singolo elemento di BiG-Log per id, con il testo intero e le relazioni (per un task: sotto-task, tag, goal, ultimi aggiornamenti, voci collegate, spese con totale, responsabile e collaboratori). Gli id si trovano con `elenca` o `cerca`.')]
#[IsReadOnly]
class Leggi extends Tool
{
    public function handle(Request $request): Response
    {
        $dati = $request->validate([
            'entita'             => 'required|in:' . implode(',', Catalogo::nomi()),
            'id'                 => 'required|integer|min:1',
            'includi_archiviati' => 'nullable|boolean',
        ]);

        try {
            $elemento = Catalogo::per($dati['entita'])->leggi((int) $dati['id'], (bool) ($dati['includi_archiviati'] ?? false));
        } catch (RegolaDominio $e) {
            return Response::error($e->getMessage());
        }

        return Response::json(['entita' => $dati['entita'], 'elemento' => $elemento]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'entita'             => $schema->string()->enum(Catalogo::nomi())->required(),
            'id'                 => $schema->integer()->required(),
            'includi_archiviati' => $schema->boolean()->description('Permette di leggere anche un elemento archiviato.'),
        ];
    }
}
