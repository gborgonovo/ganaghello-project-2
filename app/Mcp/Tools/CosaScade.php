<?php

namespace App\Mcp\Tools;

use App\Queries\AlberoAree;
use App\Queries\Scadenze;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('cosa_scade')]
#[Description('Task non chiusi in scadenza nei prossimi giorni, ordinati per data, con gli scaduti inclusi salvo richiesta. Con area_id comprende anche le sotto-aree.')]
#[IsReadOnly]
class CosaScade extends Tool
{
    public function handle(Request $request): Response
    {
        $dati = $request->validate([
            'giorni'          => 'nullable|integer|min:0|max:365',
            'area_id'         => 'nullable|integer|exists:areas,id',
            'includi_scaduti' => 'nullable|boolean',
        ]);

        $giorni = (int) ($dati['giorni'] ?? 14);
        $aree   = isset($dati['area_id']) ? AlberoAree::conDiscendenti((int) $dati['area_id']) : null;
        $oggi   = today();

        $task = app(Scadenze::class)
            ->query($giorni, (bool) ($dati['includi_scaduti'] ?? true), $aree)
            ->limit(100)->get()
            ->map(fn ($t) => [
                'id'        => $t->id,
                'titolo'    => $t->title,
                'scadenza'  => $t->due_date->toDateString(),
                'tra_giorni'=> (int) $oggi->diffInDays($t->due_date, false),
                'stage'     => $t->stage?->code,
                'area'      => $t->area?->name,
                'url'       => route('tasks.show', $t->id),
            ]);

        return Response::json(['oggi' => $oggi->toDateString(), 'giorni' => $giorni, 'totale' => $task->count(), 'task' => $task->all()]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'giorni'          => $schema->integer()->description('Finestra in giorni da oggi (predefinito 14).'),
            'area_id'         => $schema->integer(),
            'includi_scaduti' => $schema->boolean()->description('Include i task già scaduti (predefinito sì).'),
        ];
    }
}
