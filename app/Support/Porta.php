<?php

namespace App\Support;

/** Da quale porta arriva una scrittura: l'interfaccia web (predefinita) o il server MCP. */
class Porta
{
    public const INTERFACCIA = 'interfaccia';
    public const MCP         = 'mcp';

    private string $corrente = self::INTERFACCIA;

    public function imposta(string $porta): void
    {
        $this->corrente = $porta;
    }

    public function corrente(): string
    {
        return $this->corrente;
    }
}
