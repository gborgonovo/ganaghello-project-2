<?php

namespace App\Mcp\Tools;

use App\Actions\AggiornamentoActions;
use App\Actions\SpesaActions;
use App\Actions\TaskActions;
use App\Exceptions\RegolaDominio;
use App\Models\Expense;
use App\Models\Task;
use App\Models\TaskUpdate;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Name('elimina')]
#[Description('Elimina un elemento in modo recuperabile (si annulla con `ripristina`), come il pulsante Elimina dell\'app. Non confonderlo con lo stage «archiviato» di un task, che si imposta con `sposta_stage`. Un task con sotto-task si elimina insieme a loro solo con con_sottotask e conferma: senza conferma il tool mostra cosa eliminerebbe. Prima di eliminare chiedi sempre conferma all\'utente.')]
#[IsDestructive]
class Elimina extends StrumentoScrittura
{
    public const ENTITA = ['task', 'aggiornamento', 'spesa'];

    protected function esegui(Request $request): Response
    {
        $d = $request->validate([
            'entita'        => 'required|in:' . implode(',', self::ENTITA),
            'id'            => 'required|integer',
            'con_sottotask' => 'nullable|boolean',
            'conferma'      => 'nullable|boolean',
        ]);
        $u  = $request->user();
        $id = (int) $d['id'];

        switch ($d['entita']) {
            case 'task':
                $task = Task::find($id) ?? throw new RegolaDominio("Nessun task con id {$id}.");
                if (!empty($d['con_sottotask']) && empty($d['conferma'])) {
                    try {
                        app(TaskActions::class)->elimina($u, $task, 'errore');
                    } catch (RegolaDominio $e) {
                        return Response::json(['azione' => 'nessuna, serve conferma', 'eliminerebbe' => $e->getMessage(),
                            'come_procedere' => 'Richiama con con_sottotask e conferma a true dopo che l\'utente ha confermato.']);
                    }

                    return $this->risposta('task', $task->id, 'eliminato (recuperabile)');
                }
                $ids = app(TaskActions::class)->elimina($u, $task, !empty($d['con_sottotask']) ? 'includi' : 'errore');

                return Response::json(['azione' => 'eliminati (recuperabili)', 'entita' => 'task', 'id' => $ids]);

            case 'aggiornamento':
                app(AggiornamentoActions::class)->elimina($u, TaskUpdate::find($id) ?? throw new RegolaDominio("Nessun aggiornamento con id {$id}."));
                break;

            case 'spesa':
                app(SpesaActions::class)->elimina($u, Expense::find($id) ?? throw new RegolaDominio("Nessuna spesa con id {$id}."));
                break;
        }

        return Response::json(['azione' => 'eliminato (recuperabile)', 'entita' => $d['entita'], 'id' => [$id]]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'entita'        => $schema->string()->enum(self::ENTITA)->required(),
            'id'            => $schema->integer()->required(),
            'con_sottotask' => $schema->boolean()->description('Solo per i task: elimina anche i sotto-task.'),
            'conferma'      => $schema->boolean()->description('Con con_sottotask: true dopo la conferma dell\'utente.'),
        ];
    }
}
