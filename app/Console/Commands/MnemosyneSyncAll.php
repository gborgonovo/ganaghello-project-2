<?php

namespace App\Console\Commands;

use App\Jobs\SyncAreaToMnemosyne;
use App\Jobs\SyncEntryToMnemosyne;
use App\Jobs\SyncGoalToMnemosyne;
use App\Jobs\SyncTaskToMnemosyne;
use App\Models\Area;
use App\Models\Entry;
use App\Models\Goal;
use App\Models\Task;
use Illuminate\Console\Command;

class MnemosyneSyncAll extends Command
{
    protected $signature   = 'mnemosyne:sync-all {--only= : Limita a un tipo: areas|goals|tasks|entries}';
    protected $description = 'Accoda la sincronizzazione di tutte le entita verso Mnemosyne (backfill). Richiede un worker di coda attivo.';

    public function handle(): int
    {
        if (!config('services.mnemosyne.sync', true)) {
            $this->warn('La sincronizzazione e disattivata (services.mnemosyne.sync=false). Niente da accodare.');
            return self::SUCCESS;
        }

        $only = $this->option('only');

        // Ordine: aree e goal prima (sono target di relazioni), poi task, poi diario.
        if (!$only || $only === 'areas') {
            $n = 0;
            Area::query()->each(function ($a) use (&$n) { SyncAreaToMnemosyne::dispatch($a->id); $n++; });
            $this->info("Aree accodate: $n");
        }
        if (!$only || $only === 'goals') {
            $n = 0;
            Goal::query()->each(function ($g) use (&$n) { SyncGoalToMnemosyne::dispatch($g->id); $n++; });
            $this->info("Goal accodati: $n");
        }
        if (!$only || $only === 'tasks') {
            $n = 0;
            Task::query()->each(function ($t) use (&$n) { SyncTaskToMnemosyne::dispatch($t->id); $n++; });
            $this->info("Task accodati: $n");
        }
        if (!$only || $only === 'entries') {
            $n = 0;
            Entry::query()->each(function ($e) use (&$n) { SyncEntryToMnemosyne::dispatch($e->id); $n++; });
            $this->info("Voci di diario accodate: $n");
        }

        $this->newLine();
        $this->info('Fatto. Avvia un worker per processarli: php artisan queue:work');
        return self::SUCCESS;
    }
}
