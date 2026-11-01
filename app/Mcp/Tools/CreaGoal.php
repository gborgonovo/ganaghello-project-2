<?php

namespace App\Mcp\Tools;

use App\Actions\GoalActions;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('crea_goal')]
#[Description('Crea un obiettivo strategico (goal), eventualmente sotto un goal padre. I task si collegano ai goal con `collega_goal`.')]
class CreaGoal extends StrumentoScrittura
{
    protected function esegui(Request $request): Response
    {
        $el = app(GoalActions::class)->crea($request->user(), $this->campi($request, ['titolo' => 'title', 'descrizione' => 'description', 'scadenza' => 'deadline', 'stato' => 'status', 'padre_id' => 'parent_goal_id']));

        return $this->risposta('goal', $el->id, 'creato');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'titolo'      => $schema->string()->required(),
            'descrizione' => $schema->string(),
            'scadenza'    => $schema->string()->description('AAAA-MM-GG.'),
            'stato'       => $schema->string()->enum(GoalActions::STATI),
            'padre_id'    => $schema->integer()->description('Id del goal padre.'),
        ];
    }
}
