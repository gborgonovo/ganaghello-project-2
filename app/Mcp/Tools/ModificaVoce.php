<?php

namespace App\Mcp\Tools;

use App\Actions\VoceActions;
use App\Exceptions\RegolaDominio;
use App\Models\Entry;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('modifica_voce')]
#[Description('Corregge una voce di diario. Cambiano solo i campi passati; task_ids sostituisce i task collegati. Per il testo valgono le stesse regole di `crea_voce`: le parole dell\'utente, pulite dal parlato, mostrate e approvate prima di salvare. La foto si carica dall\'app, con il link url_aggiungi_foto.')]
#[IsIdempotent]
class ModificaVoce extends StrumentoScrittura
{
    use CampiVoce;

    protected function esegui(Request $request): Response
    {
        $request->validate(['id' => 'required|integer']);
        $voce = Entry::find($request->get('id')) ?? throw new RegolaDominio("Nessuna voce con id {$request->get('id')}.");
        $dati = $this->campi($request, $this->mappaVoce());
        if (!$dati) {
            throw new RegolaDominio('Nessun campo da modificare.');
        }

        $voce = app(VoceActions::class)->modifica($request->user(), $voce, $dati, $voce->attachments()->exists());

        return $this->risposta('voce', $voce->id, 'modificata', $this->linkFoto($voce));
    }

    public function schema(JsonSchema $schema): array
    {
        return ['id' => $schema->integer()->required(), ...$this->schemaVoce($schema)];
    }
}
