<?php

namespace App\Mcp\Tools;

use App\Queries\RicercaGlobale;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('cerca')]
#[Description('Ricerca libera in BiG-Log, la stessa della barra di ricerca dell\'app: cerca in task, voci di diario, note, ispirazioni e post e restituisce i risultati raggruppati per tipo, con id per poi usare `leggi`. Per filtri precisi (area, date, stage) usa `elenca`.')]
#[IsReadOnly]
class Cerca extends Tool
{
    public function handle(Request $request): Response
    {
        $dati = $request->validate([
            'testo'  => 'required|string|min:2',
            'entita' => 'nullable|in:' . implode(',', RicercaGlobale::TIPI),
            'limite' => 'nullable|integer|min:1|max:20',
        ]);

        $trovati = app(RicercaGlobale::class)->cerca(
            $dati['testo'],
            isset($dati['entita']) ? [$dati['entita']] : null,
            $dati['limite'] ?? null,
        );

        $risultati = collect($trovati)->map(fn ($modelli, $tipo) => $modelli->map(fn ($m) => array_filter([
            'id'     => $m->id,
            'titolo' => $m->title ?: Str::limit((string) ($m->content ?? $m->description ?? ''), 80),
            'stage'  => $tipo === 'task' ? $m->stage?->code : null,
            'data'   => $tipo === 'voce' ? $m->entry_date?->toDateString() : null,
            'visibilita' => $tipo === 'post' ? $m->visibility : null,
        ], fn ($v) => $v !== null && $v !== ''))->all())->all();

        return Response::json(['testo' => $dati['testo'], 'risultati' => $risultati ?: (object) []]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'testo'  => $schema->string()->description('Almeno due caratteri.')->required(),
            'entita' => $schema->string()->enum(RicercaGlobale::TIPI)->description('Limita la ricerca a un tipo.'),
            'limite' => $schema->integer()->description('Risultati per tipo (predefinito: 5 task, 4 per gli altri tipi; massimo 20).'),
        ];
    }
}
