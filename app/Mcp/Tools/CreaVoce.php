<?php

namespace App\Mcp\Tools;

use App\Actions\VoceActions;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('crea_voce')]
#[Description(<<<'TXT'
Crea una voce del diario di Ganaghello a partire da quello che l'utente ha raccontato. Il diario è suo e privato: tu trascrivi, l'autore è lui.

Prima di chiamare il tool, sempre:
1. Metti in forma il testo con la "pulizia del parlato": punteggiatura, maiuscole, a capo, correzione degli errori di dettatura, via intercalari ("allora", "cioè", "e niente") e ripetizioni. Tieni le sue parole e l'ordine dei fatti. Non aggiungere fatti, non abbellire, non cambiare tono, non commentare.
2. Proponi un titolo breve preso dalle sue parole.
3. Data e ora: se non dice altro, oggi e adesso (non passare data né ora). Se racconta un altro giorno ("ieri", "sabato"), passa quella data e l'ora solo se la dice. La voce si scrive sempre al presente del giorno raccontato: "Oggi…", mai "Ieri…", anche se la detta in un giorno successivo.
4. Chiedi se aggiungerà una foto. Se sì, passa foto_in_arrivo: la voce nasce come polaroid con un segnaposto.
5. Mostragli titolo, testo esatto, data, area e task collegati, e salva solo dopo il suo ok.

Dopo: conferma con il link alla voce; se ha detto che aggiungerà una foto, dagli il link url_aggiungi_foto, che apre l'editor dove caricarla.
TXT)]
class CreaVoce extends StrumentoScrittura
{
    use CampiVoce;

    protected function esegui(Request $request): Response
    {
        $request->validate(['testo' => 'required|string', 'foto_in_arrivo' => 'nullable|boolean']);
        $dati = $this->campi($request, $this->mappaVoce());

        $voce = app(VoceActions::class)->crea($request->user(), $dati, conFoto: (bool) $request->get('foto_in_arrivo'));

        return $this->risposta('voce', $voce->id, 'creata', $this->linkFoto($voce));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->schemaVoce($schema),
            'testo'          => $schema->string()->description('Il racconto, messo in forma come da istruzioni e approvato dall\'utente.')->required(),
            'foto_in_arrivo' => $schema->boolean()->description('L\'utente aggiungerà una foto: la voce nasce polaroid con un segnaposto.'),
        ];
    }
}
