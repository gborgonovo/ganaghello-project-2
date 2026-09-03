<?php

namespace App\Jobs;

use App\Mail\DailyDigest;
use App\Models\DigestReminder;
use App\Models\NotificationLog;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use App\Services\MnemosyneService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDailyDigest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Soglie attive di default, in giorni. Ogni soglia vale prima e dopo la scadenza. */
    private const DEFAULT_OFFSETS = [0, 1, 3, 7, 15, 30];

    /** @var int[]|null memo degli stage chiusi */
    private ?array $doneStageIds = null;

    public function handle(MnemosyneService $mnemosyne): void
    {
        $users  = User::all();
        $failed = 0;

        foreach ($users as $user) {
            try {
                $this->sendForUser($user, $mnemosyne);
            } catch (\Throwable $e) {
                $failed++;
                Log::error("SendDailyDigest: errore per user {$user->id}: {$e->getMessage()}");
            }
        }

        // La giornata si segna come fatta solo se nessun invio e' fallito: altrimenti un
        // errore la "brucerebbe" e il digest non verrebbe piu' ritentato fino al giorno dopo.
        if ($failed === 0) {
            Setting::set('digest.last_sent_date', now()->toDateString());
        }
    }

    private function sendForUser(User $user, MnemosyneService $mnemosyne): void
    {
        if (Setting::get('digest.enabled', '1') !== '1') {
            return;
        }

        $offsets   = $this->enabledOffsets();
        $withMnemo = Setting::get('digest.mnemosyne', '1') === '1';
        $today     = Carbon::today();

        // Un solo bucket per task: la soglia attiva raggiunta oggi (prima, giorno stesso
        // o ritardo). Nessun match => quel task non entra nel digest di oggi.
        $reminders = $this->dueRemindersFor($user, $offsets, $today);
        if ($reminders->isEmpty()) {
            return;
        }

        // Dedup: scarta gli offset gia' inviati per questo utente, anche in giorni passati.
        // Cosi' un giorno saltato dal worker viene recuperato ma senza doppioni.
        $sent = DigestReminder::where('user_id', $user->id)
            ->get(['task_id', 'offset'])
            ->map(fn ($r) => $r->task_id . ':' . $r->offset)
            ->flip();

        $reminders = $reminders->reject(
            fn ($r) => $sent->has($r['task']->id . ':' . $r['offset'])
        )->values();

        if ($reminders->isEmpty()) {
            return;
        }

        $groups = $this->buildGroups($reminders);

        // I dormienti viaggiano solo in aggiunta a un promemoria di scadenza, mai da soli.
        $dormant = [];
        $mnemoOk = false;

        if ($withMnemo) {
            try {
                $briefing = $mnemosyne->briefing();
                if ($briefing !== null) {
                    $mnemoOk = true;
                    $dormant = $this->buildDormant($briefing['dormant'] ?? []);
                }
            } catch (\Throwable) {
                // Mnemosyne down: digest senza sezione dormienti
            }
        }

        Mail::to($user->email)->send(new DailyDigest(
            groups:   $groups,
            dormant:  $dormant,
            userName: $user->name,
        ));

        // I promemoria si registrano solo a invio riuscito: un errore verra' ritentato.
        DigestReminder::insert($reminders->map(fn ($r) => [
            'user_id' => $user->id,
            'task_id' => $r['task']->id,
            'offset'  => $r['offset'],
            'sent_on' => $today->toDateString(),
        ])->all());

        NotificationLog::create([
            'user_id' => $user->id,
            'type'    => 'digest',
            'payload' => [
                'tasks_count'   => $reminders->count(),
                'dormant_count' => count($dormant),
                'mnemosyne_ok'  => $mnemoOk,
            ],
        ]);
    }

    /** Soglie attive (giorni), ordinate: 0 = giorno stesso, poi 1/3/7/15/30. */
    private function enabledOffsets(): array
    {
        $raw = json_decode(
            Setting::get('digest.thresholds', json_encode(self::DEFAULT_OFFSETS)),
            true
        );

        $offsets = array_values(array_unique(array_filter(
            array_map('intval', is_array($raw) ? $raw : []),
            fn ($d) => $d >= 0
        )));
        sort($offsets);

        return $offsets;
    }

    /**
     * Per ogni task aperto con scadenza, il bucket attivo oggi (o niente).
     *
     * @return Collection<int,array{task:Task,offset:int,days_until:int}>
     */
    private function dueRemindersFor(User $user, array $offsets, Carbon $today): Collection
    {
        if ($offsets === []) {
            return collect();
        }

        $maxOffset    = max($offsets);
        $afterOffsets = array_values(array_filter($offsets, fn ($d) => $d > 0));
        $monthlyTail  = in_array(30, $offsets, true);

        // Tutti gli scaduti (di qualsiasi eta', per la coda mensile) piu' gli in arrivo
        // fino alla soglia massima: due_date <= oggi + maxOffset li copre entrambi.
        $tasks = $this->openTasksFor($user)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $today->copy()->addDays($maxOffset)->toDateString())
            ->get();

        return $tasks->reduce(function (Collection $carry, Task $task) use ($offsets, $afterOffsets, $monthlyTail, $today) {
            $daysUntil = $this->daysUntil($task->due_date, $today);

            $offset = $daysUntil >= 0
                ? $this->upcomingBucket($daysUntil, $offsets)
                : $this->overdueBucket(-$daysUntil, $afterOffsets, $monthlyTail);

            if ($offset !== null) {
                $carry->push(['task' => $task, 'offset' => $offset, 'days_until' => $daysUntil]);
            }

            return $carry;
        }, collect());
    }

    /** Giorni interi da oggi alla scadenza: >0 futuro, 0 oggi, <0 in ritardo. */
    private function daysUntil(Carbon $due, Carbon $today): int
    {
        return (int) round($today->diffInDays($due->copy()->startOfDay(), false));
    }

    /**
     * Bucket per un task non ancora scaduto: la soglia attiva piu' vicina che il task
     * ha gia' raggiunto. Ritorna l'offset da registrare (0 oppure -N), o null se la
     * scadenza e' ancora oltre la soglia massima.
     */
    private function upcomingBucket(int $daysUntil, array $offsets): ?int
    {
        if ($daysUntil === 0) {
            return in_array(0, $offsets, true) ? 0 : null;
        }

        foreach ($offsets as $d) {
            if ($d > 0 && $daysUntil <= $d) {
                return -$d;
            }
        }

        return null;
    }

    /**
     * Bucket per un task scaduto da $over giorni: la soglia "dopo" attiva piu' alta
     * gia' raggiunta; oltre i 60 giorni, il multiplo di 30 corrente (coda mensile,
     * solo se la soglia 30 e' attiva). Null se nessuna soglia dopo e' attiva.
     */
    private function overdueBucket(int $over, array $afterOffsets, bool $monthlyTail): ?int
    {
        $bucket = null;

        foreach ($afterOffsets as $a) {
            if ($a <= $over) {
                $bucket = $a;
            }
        }

        if ($monthlyTail && $over >= 60) {
            $bucket = intdiv($over, 30) * 30;
        }

        return $bucket;
    }

    /**
     * Raggruppa i promemoria per distanza dalla scadenza, in ordine: in arrivo
     * (Oggi, Domani, Tra N giorni) e poi scaduti (Scaduto da N giorni).
     *
     * @param  Collection<int,array{task:Task,offset:int,days_until:int}>  $reminders
     * @return array<int,array{label:string,late:bool,tasks:array<int,array{id:int,title:string,area:?string}>}>
     */
    private function buildGroups(Collection $reminders): array
    {
        $groups = [];

        foreach ($reminders as $r) {
            $d   = $r['days_until'];
            $key = $d >= 0 ? $d : 1000 + (-$d);

            $groups[$key]['label'] = match (true) {
                $d === 0  => 'Oggi',
                $d === 1  => 'Domani',
                $d > 1    => "Tra {$d} giorni",
                $d === -1 => 'Scaduto da 1 giorno',
                default   => 'Scaduto da ' . (-$d) . ' giorni',
            };
            $groups[$key]['late']    = $d < 0;
            $groups[$key]['tasks'][] = [
                'id'    => $r['task']->id,
                'title' => $r['task']->title,
                'area'  => $r['task']->area?->name,
            ];
        }

        ksort($groups);

        return array_values($groups);
    }

    /** Task aperti di cui l'utente e' proprietario, assegnatario o collaboratore. */
    private function openTasksFor(User $user)
    {
        return Task::with('area')
            ->whereNull('deleted_at')
            ->whereNotIn('stage_id', $this->doneStageIds())
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('assigned_to', $user->id)
                  ->orWhereHas('collaborators', fn($c) => $c->where('user_id', $user->id));
            });
    }

    private function doneStageIds(): array
    {
        return $this->doneStageIds ??= \App\Models\Stage::whereIn('code', ['done', 'archiviato'])->pluck('id')->all();
    }

    private function buildDormant(array $nodes): array
    {
        $mnemo  = app(MnemosyneService::class);
        $result = [];

        foreach (array_slice($nodes, 0, 5) as $node) {
            $entity = $mnemo->resolveNode($node['name'] ?? '');

            if ($entity === null) {
                continue;
            }

            $daysInactive = $entity['updated_at']
                ? (int) $entity['updated_at']->diffInDays(now())
                : ($node['days_inactive'] ?? 0);

            $result[] = [
                'label'         => $entity['label'],
                'url'           => $entity['url'],
                'days_inactive' => $daysInactive,
            ];
        }

        return $result;
    }
}
