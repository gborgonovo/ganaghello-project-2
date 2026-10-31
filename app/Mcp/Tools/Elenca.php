<?php

namespace App\Mcp\Tools;

use App\Exceptions\RegolaDominio;
use App\Models\Entry;
use App\Queries\Entita\Catalogo;
use App\Queries\Entita\Entita;
use App\Queries\Entita\SpesaEntita;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('elenca')]
#[Description(<<<'TXT'
Elenca gli elementi di un'entità di BiG-Log con filtri e paginazione. Restituisce totale, limite, offset ed elementi; i testi lunghi sono troncati (usa `leggi` per il testo intero).

Filtri per entità (un filtro non ammesso per l'entità scelta dà errore):
- task: area_id, includi_sottoaree, stage (uno o più codici), aperti (esclude done e archiviato), tag (nome), goal_id, priorita, executor, assegnato_a (id utente), padre_id, scadenza_entro_giorni, scaduti, testo
- aggiornamento: task_id (obbligatorio)
- goal: stato, padre_id, testo
- area: padre_id, testo
- nota: area_id, includi_sottoaree, testo
- tag: testo
- spesa: task_id, area_id, includi_sottoaree, categoria, fornitore, dal, al (restituisce anche la somma degli importi filtrati)
- voce (di diario): area_id, includi_sottoaree, task_id, tipo, dal, al, testo
- post: visibilita, area_id, includi_sottoaree, testo
- ispirazione: area_id, includi_sottoaree, testo

Gli id di aree, goal, utenti e i codici degli stage si trovano con `riferimenti`. Gli archiviati sono esclusi salvo includi_archiviati.
TXT)]
#[IsReadOnly]
class Elenca extends Tool
{
    public function handle(Request $request): Response
    {
        $dati = $request->validate([
            'entita'             => 'required|in:' . implode(',', Catalogo::nomi()),
            'limite'             => 'nullable|integer|min:1|max:' . Entita::LIMITE_MASSIMO,
            'offset'             => 'nullable|integer|min:0',
            'includi_archiviati' => 'nullable|boolean',
            'stage'              => 'nullable|array',
            'stage.*'            => 'string',
            'dal'                => 'nullable|date_format:Y-m-d',
            'al'                 => 'nullable|date_format:Y-m-d',
            'scadenza_entro_giorni' => 'nullable|integer|min:0|max:3650',
        ]);

        $filtri = collect($request->all())
            ->only(Catalogo::tuttiIFiltri())
            ->reject(fn ($v) => $v === null || $v === '' || $v === false || $v === [])
            ->all();

        try {
            $risultato = Catalogo::per($dati['entita'])->elenca(
                $filtri,
                (int) ($dati['limite'] ?? Entita::LIMITE_PREDEFINITO),
                (int) ($dati['offset'] ?? 0),
                (bool) ($dati['includi_archiviati'] ?? false),
            );
        } catch (RegolaDominio $e) {
            return Response::error($e->getMessage());
        }

        return Response::json(['entita' => $dati['entita']] + $risultato);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'entita'                => $schema->string()->enum(Catalogo::nomi())->description('Entità da elencare.')->required(),
            'limite'                => $schema->integer()->description('Quanti elementi (predefinito 20, massimo 100).'),
            'offset'                => $schema->integer()->description('Da quale elemento partire, per la pagina successiva.'),
            'includi_archiviati'    => $schema->boolean()->description('Include gli elementi archiviati.'),
            'testo'                 => $schema->string()->description('Cerca nel testo (titolo, descrizione, contenuto).'),
            'area_id'               => $schema->integer(),
            'includi_sottoaree'     => $schema->boolean()->description('Con area_id, include anche le sotto-aree.'),
            'stage'                 => $schema->array()->items($schema->string()->enum(['idea', 'discussione', 'approvato', 'todo', 'doing', 'in_attesa', 'done', 'archiviato'])),
            'aperti'                => $schema->boolean()->description('Solo task non chiusi (esclude done e archiviato).'),
            'tag'                   => $schema->string()->description('Nome del tag.'),
            'goal_id'               => $schema->integer(),
            'priorita'              => $schema->string()->enum(['bassa', 'media', 'alta']),
            'executor'              => $schema->string()->enum(['una_persona', 'team', 'professionista', 'impresa']),
            'assegnato_a'           => $schema->integer()->description('Id utente responsabile.'),
            'padre_id'              => $schema->integer()->description('Id del task, goal o area padre.'),
            'scadenza_entro_giorni' => $schema->integer()->description('Task non chiusi con scadenza da oggi a oggi + N giorni.'),
            'scaduti'               => $schema->boolean()->description('Task non chiusi con scadenza passata; con scadenza_entro_giorni li aggiunge.'),
            'task_id'               => $schema->integer(),
            'stato'                 => $schema->string()->enum(['active', 'raggiunto', 'abbandonato'])->description('Stato del goal.'),
            'categoria'             => $schema->string()->enum(SpesaEntita::CATEGORIE),
            'fornitore'             => $schema->string(),
            'tipo'                  => $schema->string()->enum(Entry::KINDS)->description('Tipo della voce di diario.'),
            'visibilita'            => $schema->string()->enum(['private', 'draft', 'public']),
            'dal'                   => $schema->string()->description('Data iniziale AAAA-MM-GG.'),
            'al'                    => $schema->string()->description('Data finale AAAA-MM-GG.'),
        ];
    }
}
