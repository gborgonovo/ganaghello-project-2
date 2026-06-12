<?php

namespace App\Livewire;

use App\Models\Area;
use App\Models\Stage;
use App\Models\Task;
use App\Services\MnemosyneService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Cruscotto extends Component
{
    public string $sortAree = 'calde';

    public function render()
    {
        $oggi    = Carbon::today();
        $tra14gg = $oggi->copy()->addDays(14);

        // Tessere area
        $aree = $this->buildTessereArea();

        if ($this->sortAree === 'calde') {
            $aree = $aree->sortByDesc('calore')->values();
        } else {
            $aree = $aree->sortBy('calore')->values();
        }

        // Scadenze imminenti (prossimi 14 gg)
        $scadenze = Task::with(['area', 'stage'])
            ->whereNull('completed_at')
            ->whereNull('deleted_at')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$oggi, $tra14gg])
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        // Dormienti Mnemosyne (fault-tolerant)
        $briefing  = null;
        $dormienti = collect();
        try {
            $briefing  = app(MnemosyneService::class)->briefing();
            $dormienti = collect($briefing['dormant'] ?? [])->take(5);
        } catch (\Throwable) {
        }

        // Giorni dall'ultima visita
        $ultimaVisita = Auth::user()->updated_at ?? now();
        $giorniAssenza = (int) $ultimaVisita->diffInDays(now());

        return view('livewire.cruscotto', [
            'aree'          => $aree,
            'scadenze'      => $scadenze,
            'dormienti'     => $dormienti,
            'giorniAssenza' => $giorniAssenza,
            'mnemoOk'       => $briefing !== null,
            'sortAree'      => $this->sortAree,
        ])->layout('layouts.app', ['title' => 'Cruscotto']);
    }

    private function buildTessereArea()
    {
        $doingCodes    = ['doing', 'in_attesa'];
        $decisCodes    = ['idea', 'discussione', 'approvato'];
        $dormienzaGg   = 60;
        $sogliaDorm    = now()->subDays($dormienzaGg);

        return Area::with(['children' => fn($q) => $q->orderBy('sequence')])
            ->whereNull('parent_area_id')
            ->orderBy('sequence')
            ->get()
            ->map(function (Area $area) use ($doingCodes, $decisCodes, $sogliaDorm) {

                // Task aperti per quest'area
                $taskQuery = Task::where('area_id', $area->id)
                    ->whereNull('completed_at')
                    ->whereNull('deleted_at');

                $taskAperti = $taskQuery->count();

                // Stage attivi
                $stagesCodes = (clone $taskQuery)
                    ->join('stages', 'tasks.stage_id', '=', 'stages.id')
                    ->pluck('stages.code')
                    ->toArray();

                // Stato di sintesi
                $statoDerivato = 'vuota';
                if (!empty(array_intersect($doingCodes, $stagesCodes))) {
                    $statoDerivato = 'cantiere';
                } elseif (!empty($stagesCodes) && empty(array_diff($stagesCodes, $decisCodes))) {
                    $statoDerivato = 'in_valutazione';
                } elseif ($taskAperti > 0) {
                    $statoDerivato = 'attiva';
                }

                $stato = $area->status ?: $statoDerivato;

                // Ultimo movimento: ultima task_update o entry piu' recente
                $ultimaTaskUpdate = DB::table('task_updates')
                    ->join('tasks', 'task_updates.task_id', '=', 'tasks.id')
                    ->where('tasks.area_id', $area->id)
                    ->max('task_updates.created_at');

                $ultimaEntry = DB::table('entries')
                    ->where('area_id', $area->id)
                    ->max('updated_at');

                $tsMax = max(
                    $ultimaTaskUpdate ? Carbon::parse($ultimaTaskUpdate) : Carbon::createFromTimestamp(0),
                    $ultimaEntry      ? Carbon::parse($ultimaEntry)      : Carbon::createFromTimestamp(0)
                );
                $ultimoMovimento = $tsMax->gt(Carbon::createFromTimestamp(0)) ? $tsMax : null;

                if (!$area->status && $ultimoMovimento && $ultimoMovimento->lt($sogliaDorm)) {
                    $stato = 'dormiente';
                }

                // Prossima scadenza
                $prossima = Task::where('area_id', $area->id)
                    ->whereNull('completed_at')
                    ->whereNull('deleted_at')
                    ->whereNotNull('due_date')
                    ->where('due_date', '>=', now()->toDateString())
                    ->orderBy('due_date')
                    ->first();

                // Calore (per ordinamento "calde")
                $calore = match($stato) {
                    'cantiere'      => 4,
                    'attiva'        => 3,
                    'in_valutazione'=> 2,
                    'vuota'         => 1,
                    'dormiente'     => 0,
                    default         => 1,
                };

                // Conteggi task aperti per i figli
                if ($area->children->isNotEmpty()) {
                    $childIds = $area->children->pluck('id');
                    $counts   = Task::whereIn('area_id', $childIds)
                        ->whereNull('completed_at')
                        ->whereNull('deleted_at')
                        ->selectRaw('area_id, count(*) as cnt')
                        ->groupBy('area_id')
                        ->pluck('cnt', 'area_id');
                    $area->children->each(
                        fn($c) => $c->open_tasks_count = $counts[$c->id] ?? 0
                    );
                }

                return (object)[
                    'area'           => $area,
                    'stato'          => $stato,
                    'taskAperti'     => $taskAperti,
                    'ultimoMovimento'=> $ultimoMovimento,
                    'prossima'       => $prossima,
                    'calore'         => $calore,
                ];
            });
    }
}
