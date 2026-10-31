<?php

namespace App\Queries;

use App\Models\Entry;
use App\Models\Task;
use App\Services\MnemosyneService;

/** "Dove ero rimasto": dormienti da Mnemosyne arricchiti con il dato SQL, e il quadro di ripresa. */
class Ripresa
{
    public function __construct(private MnemosyneService $mnemo)
    {
    }

    /** @return array|null null se Mnemosyne non risponde */
    public function dormienti(int $limite = 10): ?array
    {
        $briefing = $this->mnemo->briefing();
        if ($briefing === null) {
            return null;
        }

        $out = [];
        foreach (array_slice($briefing['dormant'] ?? [], 0, $limite) as $nodo) {
            $nome     = $nodo['name'] ?? '';
            $entita   = $this->mnemo->resolveNode($nome);
            $task     = $entita ? Task::with(['stage', 'area'])->where('mnemosyne_node_name', $nome)->first() : null;

            $out[] = array_filter([
                'nome'          => $entita['label'] ?? $nome,
                'url'           => $entita['url'] ?? null,
                'giorni_fermo'  => $entita && $entita['updated_at'] ? (int) $entita['updated_at']->diffInDays(now()) : ($nodo['days_inactive'] ?? null),
                'descrizione'   => $nodo['properties']['description'] ?? null,
                'task_id'       => $task?->id,
                'stage'         => $task?->stage?->code,
                'area'          => $task?->area?->name,
                'scadenza'      => $task?->due_date?->toDateString(),
            ], fn ($v) => $v !== null);
        }

        return $out;
    }

    public function quadro(?array $areaIds = null): array
    {
        $riga = fn (Task $t) => [
            'id'       => $t->id,
            'titolo'   => $t->title,
            'stage'    => $t->stage?->code,
            'area'     => $t->area?->name,
            'scadenza' => $t->due_date?->toDateString(),
            'url'      => route('tasks.show', $t->id),
        ];

        $inCorso = Task::with(['stage', 'area'])
            ->whereHas('stage', fn ($s) => $s->whereIn('code', ['doing', 'in_attesa']))
            ->when($areaIds, fn ($q) => $q->whereIn('area_id', $areaIds))
            ->orderByRaw('due_date is null')->orderBy('due_date')
            ->limit(15)->get();

        $scadenze = app(Scadenze::class)->query(7, true, $areaIds)->limit(15)->get();

        $voci = Entry::with('area')
            ->when($areaIds, fn ($q) => $q->whereIn('area_id', $areaIds))
            ->orderByDesc('entry_date')->orderByDesc('id')->limit(5)->get()
            ->map(fn (Entry $e) => ['id' => $e->id, 'data' => $e->entry_date?->toDateString(), 'titolo' => $e->title, 'area' => $e->area?->name, 'url' => route('diario.show', $e->id)]);

        $dormienti = $areaIds ? null : $this->dormienti(5);

        return array_filter([
            'in_corso'               => $inCorso->map($riga)->all(),
            'scadenze_7_giorni_e_scaduti' => $scadenze->map($riga)->all(),
            'ultime_voci'            => $voci->all(),
            'dormienti'              => $dormienti,
            'avviso'                 => !$areaIds && $dormienti === null ? 'Mnemosyne non è raggiungibile: i dormienti non sono disponibili.' : null,
        ], fn ($v) => $v !== null);
    }
}
