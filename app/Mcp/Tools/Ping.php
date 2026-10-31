<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('ping')]
#[Description('Verifica il collegamento con BiG-Log: restituisce l\'utente riconosciuto e le capacità del token. Non legge né modifica dati.')]
#[IsReadOnly]
class Ping extends Tool
{
    public function handle(Request $request): Response
    {
        $user  = $request->user();
        $token = $user->currentAccessToken();

        return Response::json([
            'app'       => config('app.name'),
            'utente'    => $user->name,
            'token'     => $token?->name,
            'capacita'  => $token?->abilities ?? [],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
