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

        if ($offsets === []) {
            return;
        }

        $maxOffset = max($offsets);

        // Tutti gli scaduti (di qualsiasi eta') piu' gli in arrivo entro la soglia
        // massima: due_date <= oggi + maxOffset li copre entrambi. Questa e' anche la
        // lista che finisce nell'email.
        $rows = $this->openTasksFor($user)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $today->copy()->addDays($maxOffset)->toDateString())
            ->get()
            ->map(fn (Task $task) => [
                'task'       => $task,
                'days_until' => $this->daysUntil($task->due_date, $today),
            ]);

        if ($rows->isEmpty()) {
            return;
        }

        // Trigger: i task che colpiscono oggi una soglia esatta (o il recupero di un
        // giorno saltato) e non l'hanno gia' fatto. Decide SE inviare, non il contenuto.
        $fresh = $this->newCrossings($user, $rows, $offsets);
        if ($fresh->isEmpty()) {
            return;
        }

        // Contenuto: il quadro completo, tutte le scadenze in vista con la distanza reale,
        // non solo i crossing di oggi.
        $groups = $this->buildGroups($rows);

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

        // Si registrano solo i crossing nuovi, e solo a invio riuscito: cosi' un errore
        // viene ritentato e domani il trigger non li riscatta.
        DigestReminder::insert($fresh->map(fn ($r) => [
            'user_id' => $user->id,
            'task_id' => $r['task']->id,
            'offset'  => $r['offset'],
            'sent_on' => $today->toDateString(),
        ])->all());

        NotificationLog::create([
            'user_id' => $user->id,
            'type'    => 'digest',
            'payload' => [
                'tasks_count'    => $rows->count(),
                'dormant_count'  => count($dormant),
                'mnemosyne_ok'   => $mnemoOk,
                'trigger_count'  => $fresh->count(),
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
     * I crossing di oggi non ancora registrati: per ogni task, il bucket di soglia
     * attivo oggi, scartati quelli gia' inviati in passato (dedup + recupero).
     *
     * @param  Collection<int,array{task:Task,days_until:int}>  $rows
     * @return Collection<int,array{task:Task,offset:int}>
     */
    private function newCrossings(User $user, Collection $rows, array $offsets): Collection
    {
        $afterOffsets = array_values(array_filter($offsets, fn ($d) => $d > 0));
        $monthlyTail  = in_array(30, $offsets, true);

        $crossings = $rows->reduce(function (Collection $carry, array $row) use ($offsets, $afterOffsets, $monthlyTail) {
            $daysUntil = $row['days_until'];

            $offset = $daysUntil >= 0
                ? $this->upcomingBucket($daysUntil, $offsets)
                : $this->overdueBucket(-$daysUntil, $afterOffsets, $monthlyTail);

            if ($offset !== null) {
                $carry->push(['task' => $row['task'], 'offset' => $offset]);
            }

            return $carry;
        }, collect());

        if ($crossings->isEmpty()) {
            return $crossings;
        }

        // Dedup: un giorno saltato dal worker viene recuperato, ma una soglia parte una
        // volta sola.
        $sent = DigestReminder::where('user_id', $user->id)
            ->get(['task_id', 'offset'])
            ->map(fn ($r) => $r->task_id . ':' . $r->offset)
            ->flip();

        return $crossings->reject(
            fn ($r) => $sent->has($r['task']->id . ':' . $r['offset'])
        )->values();
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
     * Raggruppa le scadenze per distanza reale, in ordine: in arrivo (Oggi, Domani,
     * Tra N giorni) e poi scaduti (Scaduto da N giorni).
     *
     * @param  Collection<int,array{task:Task,days_until:int}>  $rows
     * @return array<int,array{label:string,late:bool,tasks:array<int,array{id:int,title:string,area:?string}>}>
     */
    private function buildGroups(Collection $rows): array
    {
        $groups = [];

        foreach ($rows as $r) {
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
