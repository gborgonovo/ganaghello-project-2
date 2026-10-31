<?php

namespace App\Mcp\Tools;

use App\Models\Area;
use App\Models\Entry;
use App\Models\Goal;
use App\Models\Stage;
use App\Models\Tag;
use App\Models\User;
use App\Queries\Entita\SpesaEntita;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('riferimenti')]
#[Description('I dati di riferimento di BiG-Log: stage (codice e nome, in ordine), albero delle aree con id e profondità, goal attivi, tag, utenti (id e nome) e i valori ammessi dei campi a scelta. Da chiamare prima di filtrare per area, goal, utente o stage.')]
#[IsReadOnly]
class Riferimenti extends Tool
{
    public function handle(Request $request): Response
    {
        $aree = Area::orderBy('sequence')->orderBy('name')->get();
        $padri = $aree->pluck('parent_area_id', 'id');
        $livello = function ($id) use ($padri, &$livello) {
            return $padri[$id] ? 1 + $livello($padri[$id]) : 0;
        };

        return Response::json([
            'stage'  => Stage::orderBy('sequence')->get()->map(fn ($s) => ['codice' => $s->code, 'nome' => $s->label])->all(),
            'aree'   => Area::optionsTree()->map(fn ($o) => [
                'id'         => $o->id,
                'nome'       => $aree->firstWhere('id', $o->id)->name,
                'padre_id'   => $padri[$o->id],
                'profondita' => $livello($o->id),
            ])->all(),
            'goal_attivi' => Goal::where('status', 'active')->orderBy('title')->get(['id', 'title'])->map(fn ($g) => ['id' => $g->id, 'titolo' => $g->title])->all(),
            'tag'    => Tag::orderBy('name')->pluck('name')->all(),
            'utenti' => User::orderBy('name')->get(['id', 'name'])->map(fn ($u) => ['id' => $u->id, 'nome' => $u->name])->all(),
            'valori' => [
                'priorita'        => ['bassa', 'media', 'alta'],
                'executor'        => ['una_persona', 'team', 'professionista', 'impresa'],
                'base_costo'      => ['stima', 'preventivo', 'reale'],
                'stato_goal'      => ['active', 'raggiunto', 'abbandonato'],
                'categoria_spesa' => SpesaEntita::CATEGORIE,
                'tipo_voce'       => Entry::KINDS,
                'visibilita_post' => ['private', 'draft', 'public'],
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
