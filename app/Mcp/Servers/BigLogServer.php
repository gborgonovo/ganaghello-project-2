<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\Ping;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('BiG-Log')]
#[Version('0.1.0')]
#[Instructions('BiG-Log è il diario di bordo e gestionale della ristrutturazione della casa di Ganaghello, futuro B&B. Contiene task e sotto-task, aggiornamenti, goal, aree, note, tag, spese, voci di diario, post e ispirazioni. I post non diventano mai pubblici da qui: la pubblicazione è un gesto dell\'utente nell\'app.')]
class BigLogServer extends Server
{
    protected array $tools = [
        Ping::class,
    ];
}
