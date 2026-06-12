<?php

namespace App\Livewire;

use App\Models\Area;
use App\Models\Goal;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class TimelineIndex extends Component
{
    public array $expandedAreas = [];

    public function toggleArea(int $id): void
    {
        if (in_array($id, $this->expandedAreas)) {
            $this->expandedAreas = array_values(
                array_filter($this->expandedAreas, fn($x) => $x !== $id)
            );
        } else {
            $this->expandedAreas[] = $id;
        }
    }

    // Raccoglie tutte le date Carbon (start + due) da un'area e tutti i suoi discendenti
    private function collectDates(Area $area): Collection
    {
        $dates = collect();
        foreach ($area->tasks as $t) {
            if ($t->start_date) $dates->push($t->start_date);
            if ($t->due_date)   $dates->push($t->due_date);
        }
        foreach ($area->children as $child) {
            $dates = $dates->merge($this->collectDates($child));
        }
        return $dates;
    }

    // Costruisce la lista flat delle righe da rendere (root + figli espansi)
    private function buildRows(Collection $areas, int $depth = 0): array
    {
        $rows = [];
        foreach ($areas as $area) {
            $dates = $this->collectDates($area);

            $rows[] = [
                'area'        => $area,
                'depth'       => $depth,
                'envStart'    => $dates->min(),
                'envEnd'      => $dates->max(),
                'tasks'       => $area->tasks
                    ->filter(fn($t) => $t->due_date || $t->start_date)
                    ->values(),
                'hasChildren' => $area->children->isNotEmpty(),
                'expanded'    => in_array($area->id, $this->expandedAreas),
            ];

            if (in_array($area->id, $this->expandedAreas) && $area->children->isNotEmpty()) {
                $rows = array_merge($rows, $this->buildRows($area->children, $depth + 1));
            }
        }
        return $rows;
    }

    public function render()
    {
        // Aree con task e figli caricati (fino a 3 livelli)
        $areas = Area::whereNull('parent_area_id')
            ->with([
                'tasks',
                'children.tasks',
                'children.children.tasks',
            ])
            ->orderBy('sequence')
            ->get();

        // Goals con deadline
        $goals = Goal::whereNull('parent_goal_id')
            ->whereNotNull('deadline')
            ->orderBy('deadline')
            ->get();

        // Calcola la finestra temporale dai dati reali
        $allDates = collect();
        foreach ($areas as $area) {
            $allDates = $allDates->merge($this->collectDates($area));
        }
        $goals->each(fn($g) => $allDates->push($g->deadline));

        $dataMin = $allDates->filter()->min();
        $dataMax = $allDates->filter()->max();

        $windowStart = ($dataMin ? Carbon::parse($dataMin)->subYear() : now()->subYear())
            ->startOfYear();
        $windowEnd   = ($dataMax ? Carbon::parse($dataMax)->addYear() : now()->addYears(3))
            ->endOfYear();

        // Assicura un minimo di 4 anni visibili
        if ($windowStart->diffInYears($windowEnd) < 4) {
            $windowEnd = $windowStart->copy()->addYears(4)->endOfYear();
        }

        $totalDays = (int) $windowStart->diffInDays($windowEnd);

        // Marcatori anno per l'asse
        $years = [];
        $y = $windowStart->copy()->startOfYear();
        while ($y->lte($windowEnd)) {
            $years[] = [
                'year' => $y->year,
                'pct'  => $windowStart->diffInDays($y) / $totalDays * 100,
            ];
            $y = $y->addYear();
        }

        $todayPct = min(100, max(0, $windowStart->diffInDays(now()) / $totalDays * 100));

        $rows = $this->buildRows($areas);

        return view('livewire.timeline-index', compact(
            'goals', 'rows', 'years', 'todayPct', 'windowStart', 'totalDays'
        ))->layout('layouts.app', ['title' => 'Timeline']);
    }
}
