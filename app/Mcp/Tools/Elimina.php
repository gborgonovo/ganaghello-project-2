<?php

namespace App\Mcp\Tools;

use App\Actions\TaskActions;
use App\Exceptions\RegolaDominio;
use App\Models\Post;
use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Name('elimina')]
#[Description('Elimina un elemento in modo recuperabile (si annulla con `ripristina`), come il pulsante Elimina dell\'app. Non confonderlo con lo stage «archiviato» di un task, che si imposta con `sposta_stage`. Un task con sotto-task si elimina insieme a loro solo con con_sottotask e conferma: senza conferma il tool mostra cosa eliminerebbe. Un post pubblicato non si elimina da qui. Prima di eliminare chiedi sempre conferma all\'utente.')]
#[IsDestructive]
class Elimina extends StrumentoScrittura
{
    protected function esegui(Request $request): Response
    {
        $d = $request->validate([
            'entita'        => 'required|in:' . implode(',', Eliminabili::nomi()),
            'id'            => 'required|integer',
            'con_sottotask' => 'nullable|boolean',
            'conferma'      => 'nullable|boolean',
        ]);
        $u        = $request->user();
        $elemento = Eliminabili::trova($d['entita'], (int) $d['id']);

        if ($elemento instanceof Task) {
            if (!empty($d['con_sottotask']) && empty($d['conferma'])) {
                try {
                    app(TaskActions::class)->elimina($u, $elemento, 'errore');
                } catch (RegolaDominio $e) {
                    return Response::json(['azione' => 'nessuna, serve conferma', 'eliminerebbe' => $e->getMessage(),
                        'come_procedere' => 'Richiama con con_sottotask e conferma a true dopo che l\'utente ha confermato.']);
                }

                return Response::json(['azione' => 'eliminato (recuperabile)', 'entita' => 'task', 'id' => [$elemento->id]]);
            }
            $ids = app(TaskActions::class)->elimina($u, $elemento, !empty($d['con_sottotask']) ? 'includi' : 'errore');

            return Response::json(['azione' => 'eliminati (recuperabili)', 'entita' => 'task', 'id' => $ids]);
        }

        if ($elemento instanceof Post && $elemento->isPublic()) {
            throw new RegolaDominio('Il post è pubblicato: per toglierlo usa l\'app.');
        }

        app(Eliminabili::actions($d['entita']))->elimina($u, $elemento);

        return Response::json(['azione' => 'eliminato (recuperabile)', 'entita' => $d['entita'], 'id' => [$elemento->id]]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'entita'        => $schema->string()->enum(Eliminabili::nomi())->required(),
            'id'            => $schema->integer()->required(),
            'con_sottotask' => $schema->boolean()->description('Solo per i task: elimina anche i sotto-task.'),
            'conferma'      => $schema->boolean()->description('Con con_sottotask: true dopo la conferma dell\'utente.'),
        ];
    }
}
