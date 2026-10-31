<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\Briefing;
use App\Mcp\Tools\Cerca;
use App\Mcp\Tools\CosaScade;
use App\Mcp\Tools\CosaSiRaffredda;
use App\Mcp\Tools\Elenca;
use App\Mcp\Tools\Leggi;
use App\Mcp\Tools\Ping;
use App\Mcp\Tools\Riferimenti;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('BiG-Log')]
#[Version('0.2.0')]
#[Instructions(<<<'TXT'
BiG-Log è il diario di bordo e gestionale della ristrutturazione della casa di Ganaghello (Castel San Giovanni, PC), futuro B&B "Borgonovo in Ganaghello". Contiene task e sotto-task con i loro aggiornamenti, goal, aree (i luoghi della casa, ad albero), note, tag, spese, voci di diario, post del blog e ispirazioni della moodboard.

Per cominciare: `briefing` dà il quadro di ripresa; `riferimenti` dà gli id di aree, goal e utenti e i codici degli stage; `elenca` e `leggi` consultano qualunque entità; `cerca` è la ricerca libera.

Regole del dominio:
- gli stage di un task, in ordine: idea, discussione, approvato (decisione), todo, doing, in_attesa (esecuzione), done, archiviato;
- un task in todo, doing o in_attesa ha sempre una scadenza;
- i post non diventano mai pubblici da qui: la pubblicazione è un gesto dell'utente nell'app.

Ogni elemento restituito ha un `url` che apre la pagina nell'app: proponilo quando l'utente vuole vedere o modificare qualcosa.
TXT)]
class BigLogServer extends Server
{
    protected array $tools = [
        Briefing::class,
        Riferimenti::class,
        Elenca::class,
        Leggi::class,
        Cerca::class,
        CosaScade::class,
        CosaSiRaffredda::class,
        Ping::class,
    ];
}
