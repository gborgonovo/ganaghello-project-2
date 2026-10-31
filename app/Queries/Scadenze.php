<?php

namespace App\Queries;

use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/** Task non chiusi con scadenza in una finestra di giorni; condivisa da cruscotto e tool MCP `cosa_scade`. */
class Scadenze
{
    public function query(int $giorni = 14, bool $includiScaduti = false, ?array $areaIds = null): Builder
    {
        $oggi = Carbon::today();
        $fine = $oggi->copy()->addDays($giorni);

        return Task::with(['area', 'stage'])
            ->whereNull('completed_at')
            ->whereNotNull('due_date')
            ->when($includiScaduti,
                fn ($q) => $q->where('due_date', '<=', $fine),
                fn ($q) => $q->whereBetween('due_date', [$oggi, $fine]))
            ->when($areaIds, fn ($q) => $q->whereIn('area_id', $areaIds))
            ->orderBy('due_date');
    }
}
