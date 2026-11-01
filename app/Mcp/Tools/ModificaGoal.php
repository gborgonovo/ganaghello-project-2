<?php

namespace App\Mcp\Tools;

use App\Actions\GoalActions;
use App\Exceptions\RegolaDominio;
use App\Models\Goal;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('modifica_goal')]
#[Description('Modifica un goal: titolo, descrizione, scadenza, stato (active, raggiunto, abbandonato), padre. Cambiano solo i campi passati.')]
#[IsIdempotent]
class ModificaGoal extends StrumentoScrittura
{
    protected function esegui(Request $request): Response
    {
        $request->validate(['id' => 'required|integer']);
        $el   = Goal::find($request->get('id')) ?? throw new RegolaDominio("Nessun elemento di tipo goal con id {$request->get('id')}.");
        $dati = $this->campi($request, ['titolo' => 'title', 'descrizione' => 'description', 'scadenza' => 'deadline', 'stato' => 'status', 'padre_id' => 'parent_goal_id']);
        if (!$dati) {
            throw new RegolaDominio('Nessun campo da modificare.');
        }

        app(GoalActions::class)->modifica($request->user(), $el, $dati);

        return $this->risposta('goal', $el->id, 'modificato');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required(),
            'titolo'      => $schema->string(),
            'descrizione' => $schema->string(),
            'scadenza'    => $schema->string()->description('AAAA-MM-GG.'),
            'stato'       => $schema->string()->enum(GoalActions::STATI),
            'padre_id'    => $schema->integer()->description('Id del goal padre.'),
        ];
    }
}
