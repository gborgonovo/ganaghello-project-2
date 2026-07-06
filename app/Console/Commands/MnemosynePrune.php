<?php

namespace App\Console\Commands;

use App\Models\Entry;
use App\Models\Goal;
use App\Models\Task;
use App\Services\MnemosyneService;
use Illuminate\Console\Command;

class MnemosynePrune extends Command
{
    protected $signature = 'mnemosyne:prune
        {--dry-run : Elenca i nodi orfani senza rimuovere nulla}
        {--only= : Limita a un tipo: tasks|goals|entries}';

    protected $description = 'Rimuove da Mnemosyne i nodi di entita cestinate (soft-deleted) rimasti orfani, es. cancellazioni la cui propagazione non era mai arrivata.';

    /** Solo i modelli sincronizzati che usano SoftDeletes (l'Area non ne ha). */
    private const MODELS = [
        'tasks'   => Task::class,
        'goals'   => Goal::class,
        'entries' => Entry::class,
    ];

    public function handle(MnemosyneService $mnemosyne): int
    {
        $dry = (bool) $this->option('dry-run');

        if (!$dry && !$mnemosyne->enabled()) {
            $this->warn('Sync disattivata (services.mnemosyne.sync=false o API key mancante): niente da rimuovere.');
            return self::SUCCESS;
        }

        $only = $this->option('only');
        $found = 0;
        $removed = 0;

        foreach (self::MODELS as $key => $class) {
            if ($only && $only !== $key) {
                continue;
            }

            $rows = $class::onlyTrashed()->whereNotNull('mnemosyne_node_name')->get();

            foreach ($rows as $row) {
                $name = $row->mnemosyne_node_name;
                $found++;

                if ($dry) {
                    $this->line("[dry] {$key} #{$row->getKey()}  ->  {$name}");
                    continue;
                }

                try {
                    $mnemosyne->deleteNode($name);   // il 404 (gia' assente) e' trattato come successo
                    $this->line("rimosso  {$key} #{$row->getKey()}  ->  {$name}");
                    $removed++;
                } catch (\Throwable $e) {
                    $this->error("errore su {$name}: " . $e->getMessage());
                }
            }
        }

        $this->newLine();
        $this->info($dry
            ? "Nodi orfani da rimuovere: {$found}"
            : "Rimossi {$removed} nodi su {$found} orfani.");

        return self::SUCCESS;
    }
}
