<?php

namespace App\Console\Commands;

use App\Models\Stage;
use App\Models\Task;
use Illuminate\Console\Command;

/**
 * Applica correzioni di stage/date ai task da un CSV (id;...;stage;scadenza;completamento).
 * Aggiorna SOLO stage_id, due_date, completed_at, per id. Non crea, non elimina,
 * non tocca altri campi. Pensato per sistemare la migrazione v1 dopo revisione a mano.
 *
 *   php artisan tasks:apply-corrections correzione-task.csv [--dry-run]
 *
 * Colonne attese (separatore ;): id ; titolo ; stage ; scadenza ; completamento ; [altre ignorate]
 * Lo stage si indica con il code (idea, done, archiviato...) o con la label (Idea, Done...).
 * Le date in YYYY-MM-DD oppure GG/MM/AAAA; vuoto = azzera.
 */
class ApplyTaskCorrections extends Command
{
    protected $signature = 'tasks:apply-corrections {csv : percorso del CSV} {--dry-run : mostra le modifiche senza salvarle}';
    protected $description = 'Applica correzioni di stage/date ai task da un CSV (per id)';

    public function handle(): int
    {
        $path = $this->argument('csv');
        if (!is_file($path)) {
            $this->error("File non trovato: {$path}");
            return self::FAILURE;
        }
        $dry = (bool) $this->option('dry-run');

        // Mappa stage: code e label (minuscolo) -> id, + sinonimi comuni
        $stageByKey = [];
        foreach (Stage::all() as $s) {
            $stageByKey[mb_strtolower($s->code)]  = $s->id;
            $stageByKey[mb_strtolower($s->label)] = $s->id;
        }
        foreach (['archivio' => 'archiviato', 'in attesa' => 'in_attesa', 'fatto' => 'done'] as $alias => $code) {
            if (isset($stageByKey[$code])) {
                $stageByKey[$alias] = $stageByKey[$code];
            }
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false || count($lines) < 2) {
            $this->error('CSV vuoto o illeggibile.');
            return self::FAILURE;
        }
        // Toglie eventuale BOM dalla prima riga; rileva il separatore dall'header (Excel salva spesso con ,)
        $lines[0] = preg_replace('/^\xEF\xBB\xBF/', '', $lines[0]);
        $header   = $lines[0];
        $sep      = substr_count($header, ';') >= substr_count($header, ',') ? ';' : ',';
        array_shift($lines);

        $updated = 0; $unchanged = 0; $errors = [];

        foreach ($lines as $n => $line) {
            $row = str_getcsv($line, $sep);
            $rowNum = $n + 2; // +header +1-based

            $id    = (int) trim($row[0] ?? '');
            $stage = trim($row[2] ?? '');
            $scad  = trim($row[3] ?? '');
            $compl = trim($row[4] ?? '');
            $canc  = trim($row[5] ?? ''); // deleted_at: data = cancella, vuoto = attivo/ripristina

            if (!$id) { $errors[] = "riga {$rowNum}: id mancante"; continue; }

            // withTrashed: così si ritrovano (ed eventualmente si ripristinano) i task già cancellati
            $task = Task::withTrashed()->find($id);
            if (!$task) { $errors[] = "riga {$rowNum}: task #{$id} non trovato"; continue; }

            $stageId = $stageByKey[mb_strtolower($stage)] ?? null;
            if ($stage === '' || $stageId === null) {
                $errors[] = "riga {$rowNum} (#{$id}): stage non valido «{$stage}»";
                continue;
            }

            $due = $this->parseDate($scad, $rowNum, $id, $errors);
            $don = $this->parseDate($compl, $rowNum, $id, $errors);
            $del = $this->parseDate($canc, $rowNum, $id, $errors);
            if ($due === false || $don === false || $del === false) { continue; }

            $changed = $task->stage_id != $stageId
                || optional($task->due_date)->format('Y-m-d') !== $due
                || optional($task->completed_at)->format('Y-m-d') !== $don
                || optional($task->deleted_at)->format('Y-m-d') !== $del;

            if (!$changed) { $unchanged++; continue; }

            // Segnala in anteprima i passaggi di stato di cancellazione
            $wasDeleted = $task->deleted_at !== null;
            $flag = match (true) {
                !$wasDeleted && $del !== null => ' [CANCELLA]',
                $wasDeleted && $del === null  => ' [RIPRISTINA]',
                default                       => '',
            };

            if ($dry) {
                $this->line(sprintf('#%d  stage:%s  scad:%s  compl:%s%s   (%s)',
                    $id, $stage, $due ?? '—', $don ?? '—', $flag, \Illuminate\Support\Str::limit($task->title, 30)));
            } else {
                $task->stage_id     = $stageId;
                $task->due_date     = $due;
                $task->completed_at = $don;
                $task->deleted_at   = $del;
                $task->saveQuietly(); // niente observer/sync Mnemosyne durante la correzione di massa
            }
            $updated++;
        }

        $this->newLine();
        $this->info(($dry ? '[DRY-RUN] ' : '') . "Aggiornati: {$updated} · invariati: {$unchanged} · errori: " . count($errors));
        foreach ($errors as $e) { $this->warn('  '.$e); }
        if (!$dry && $updated > 0) {
            $this->line('Per riallineare Mnemosyne (facoltativo): php artisan mnemosyne:sync-all');
        }

        return self::SUCCESS;
    }

    /** '' -> null ; YYYY-MM-DD o GG/MM/AAAA -> 'Y-m-d' ; non valido -> false (con errore) */
    private function parseDate(string $v, int $rowNum, int $id, array &$errors): string|null|false
    {
        if ($v === '') return null;
        if (preg_match('#^\d{4}-\d{2}-\d{2}$#', $v)) return $v;
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $v, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        $errors[] = "riga {$rowNum} (#{$id}): data non valida «{$v}» (usa AAAA-MM-GG)";
        return false;
    }
}
