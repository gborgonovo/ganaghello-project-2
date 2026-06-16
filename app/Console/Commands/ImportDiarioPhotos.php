<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use App\Models\Entry;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Console\Command;

/**
 * Importa voci di diario (tipo polaroid) da una cartella di immagini + un CSV.
 *
 *   php artisan diario:import-photos /percorso/immagini_date_descrizioni.csv [--dir=] [--user=] [--dry-run]
 *
 * CSV (separatore , con header): file,data_ora,fonte,Titolo,Descrizione
 *   - file: nome del file immagine nella cartella (--dir, default = cartella del CSV)
 *   - data_ora: "AAAA-MM-GG" oppure "AAAA-MM-GG HH:MM[:SS]"
 *   - fonte: se vale "no" la riga viene SALTATA (non si carica)
 *   - Titolo / Descrizione: opzionali
 *
 * Ogni riga importata crea una Entry (kind=polaroid) con la foto come copertina.
 * Idempotente: salta le foto gia' importate (stesso original_filename su una voce).
 */
class ImportDiarioPhotos extends Command
{
    protected $signature = 'diario:import-photos {csv : percorso del CSV}
        {--dir= : cartella delle immagini (default: quella del CSV)}
        {--user= : id utente autore (default: primo utente)}
        {--dry-run : mostra cosa farebbe senza salvare}';

    protected $description = 'Importa voci di diario (polaroid) da immagini + CSV';

    public function handle(MediaService $media): int
    {
        $csv = $this->argument('csv');
        if (!is_file($csv)) {
            $this->error("CSV non trovato: {$csv}");
            return self::FAILURE;
        }

        $dir = rtrim($this->option('dir') ?: dirname($csv), '/');
        $dry = (bool) $this->option('dry-run');

        $userId = $this->option('user') ?: optional(User::orderBy('id')->first())->id;
        if (!$userId) {
            $this->error('Nessun utente disponibile: indica --user=<id>.');
            return self::FAILURE;
        }

        $fh = fopen($csv, 'r');
        if (!$fh) {
            $this->error('Impossibile aprire il CSV.');
            return self::FAILURE;
        }

        // Header: mappa i nomi colonna -> indice (tollerante all'ordine, toglie il BOM)
        $header = fgetcsv($fh);
        if (!$header) {
            $this->error('CSV vuoto.');
            return self::FAILURE;
        }
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        $col = [];
        foreach ($header as $i => $name) {
            $col[mb_strtolower(trim($name))] = $i;
        }
        foreach (['file', 'data_ora', 'fonte'] as $required) {
            if (!isset($col[$required])) {
                $this->error("Colonna mancante nel CSV: {$required}");
                return self::FAILURE;
            }
        }

        $imported = 0; $skippedNo = 0; $skippedDup = 0; $errors = [];
        $rowNum = 1;

        while (($row = fgetcsv($fh)) !== false) {
            $rowNum++;
            if ($row === [null] || (count($row) === 1 && trim((string) $row[0]) === '')) {
                continue; // riga vuota
            }

            $get  = fn (string $key) => isset($col[$key]) ? trim((string) ($row[$col[$key]] ?? '')) : '';
            $file = $get('file');
            $dataOra = $get('data_ora');
            $fonte = mb_strtolower($get('fonte'));
            $titolo = isset($col['titolo']) ? trim((string) ($row[$col['titolo']] ?? '')) : '';
            $descr  = isset($col['descrizione']) ? trim((string) ($row[$col['descrizione']] ?? '')) : '';

            if ($file === '') { $errors[] = "riga {$rowNum}: nome file mancante"; continue; }
            if ($fonte === 'no') { $skippedNo++; continue; }

            $path = $dir . '/' . $file;
            if (!is_file($path)) { $errors[] = "riga {$rowNum}: file non trovato «{$file}»"; continue; }

            [$date, $time] = $this->parseDateTime($dataOra);
            if ($date === null) { $errors[] = "riga {$rowNum} («{$file}»): data_ora non valida «{$dataOra}»"; continue; }

            // Idempotenza: gia' importata questa foto su una voce?
            $dup = Attachment::where('attachable_type', Entry::class)
                ->whereHas('media', fn ($q) => $q->where('original_filename', $file))
                ->exists();
            if ($dup) { $skippedDup++; continue; }

            if ($dry) {
                $this->line(sprintf('+ %s  %s%s  %s', $date, $time ?? '--:--',
                    $titolo !== '' ? '  «' . \Illuminate\Support\Str::limit($titolo, 30) . '»' : '', $file));
                $imported++;
                continue;
            }

            $entry = Entry::create([
                'user_id'    => $userId,
                'area_id'    => null,
                'title'      => $titolo,
                'content'    => $descr,
                'kind'       => 'polaroid',
                'entry_date' => $date,
                'entry_time' => $time,
            ]);

            $m = $media->storeFromPath($path, $file, (int) $userId, 'diario');
            Attachment::create([
                'attachable_type' => Entry::class,
                'attachable_id'   => $entry->id,
                'media_id'        => $m->id,
                'sequence'        => 1,
            ]);

            $imported++;
        }
        fclose($fh);

        $this->newLine();
        $this->info(($dry ? '[DRY-RUN] ' : '') . "Importate: {$imported} · saltate (fonte=no): {$skippedNo} · saltate (gia' presenti): {$skippedDup} · errori: " . count($errors));
        foreach ($errors as $e) { $this->warn('  ' . $e); }

        return self::SUCCESS;
    }

    /** '' o non valido -> [null,null]. Restituisce ['Y-m-d', 'H:i'|null]. */
    private function parseDateTime(string $v): array
    {
        $v = trim($v);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v)) {
            return [$v, null];
        }
        if (preg_match('/^(\d{4}-\d{2}-\d{2})[ T](\d{2}):(\d{2})(?::\d{2})?$/', $v, $m)) {
            return [$m[1], $m[2] . ':' . $m[3]];
        }
        return [null, null];
    }
}
