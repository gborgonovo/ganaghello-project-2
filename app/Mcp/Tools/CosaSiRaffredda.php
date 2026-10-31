<?php

namespace App\Mcp\Tools;

use App\Queries\Ripresa;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('cosa_si_raffredda')]
#[Description('I fili dormienti: cose del progetto ferme da tempo secondo Mnemosyne, con giorni di inattività e, per i task, stage, area e scadenza. Se Mnemosyne non risponde restituisce un errore esplicito.')]
#[IsReadOnly]
class CosaSiRaffredda extends Tool
{
    public function handle(Request $request): Response
    {
        $dati = $request->validate(['limite' => 'nullable|integer|min:1|max:30']);

        $dormienti = app(Ripresa::class)->dormienti((int) ($dati['limite'] ?? 10));
        if ($dormienti === null) {
            return Response::error('Mnemosyne non è raggiungibile, i dormienti non sono disponibili. Gli altri tool di lettura funzionano.');
        }

        return Response::json(['dormienti' => $dormienti]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'limite' => $schema->integer()->description('Quanti dormienti (predefinito 10).'),
        ];
    }
}
