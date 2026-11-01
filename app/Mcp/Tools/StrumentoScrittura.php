<?php

namespace App\Mcp\Tools;

use App\Exceptions\RegolaDominio;
use App\Queries\Entita\Catalogo;
use App\Support\Porta;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

/**
 * Base dei tool che scrivono: visibili e utilizzabili solo con un token che ha la
 * capacità mcp:scrivere; segnano la porta MCP per il registro; traducono gli errori
 * di validazione e di dominio in messaggi leggibili.
 */
abstract class StrumentoScrittura extends Tool
{
    abstract protected function esegui(Request $request): Response;

    public function shouldRegister(): bool
    {
        return (bool) auth()->user()?->tokenCan('mcp:scrivere');
    }

    public function handle(Request $request): Response
    {
        if (!$this->shouldRegister()) {
            return Response::error('Questo token può solo leggere: per scrivere serve la capacità mcp:scrivere.');
        }

        app(Porta::class)->imposta(Porta::MCP);

        try {
            return $this->esegui($request);
        } catch (ValidationException $e) {
            return Response::error('Dati non validi: ' . collect($e->errors())->flatten()->implode(' '));
        } catch (RegolaDominio $e) {
            return Response::error($e->getMessage());
        }
    }

    /** L'elemento scritto, nella forma di `leggi`, con l'azione fatta: l'IA può confermarla all'utente con il link. */
    protected function risposta(string $entita, int $id, string $azione, array $extra = []): Response
    {
        return Response::json(['azione' => $azione, 'entita' => $entita]
            + $extra
            + ['elemento' => Catalogo::per($entita)->leggi($id, true)]);
    }

    /** Traduce i parametri italiani del tool nei campi del model, tenendo solo quelli passati (null svuota). */
    protected function campi(Request $request, array $mappa): array
    {
        $argomenti = $request->all();
        $campi     = [];
        foreach ($mappa as $parametro => $campo) {
            if (array_key_exists($parametro, $argomenti)) {
                $campi[$campo] = $argomenti[$parametro];
            }
        }

        return $campi;
    }
}
