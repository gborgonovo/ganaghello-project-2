<?php

namespace App\Jobs;

use App\Mail\DailyDigest;
use App\Models\Area;
use App\Models\Entry;
use App\Models\Goal;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDailyDigest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Soglie in giorni: chiave usata nelle impostazioni
    private const THRESHOLD_KEYS = [
        0  => 'oggi',
        1  => 'domani',
        3  => '3giorni',
        7  => '7giorni',
        30 => '30giorni',
    ];

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
        $enabled    = Setting::get('digest.enabled', '1') === '1';
        $thresholds = json_decode(Setting::get('digest.thresholds', json_encode([0, 1, 3, 7, 30])), true);
        $withMnemo  = Setting::get('digest.mnemosyne', '1') === '1';

        if (!$enabled) return;

        $today = Carbon::today();

        $sections  = [];
        $taskCount = 0;

        // Scaduti: aperti con la scadenza gia' passata. Il digest guardava solo in avanti,
        // quindi una scadenza mancata non veniva piu' ricordata.
        $overdue = $this->openTasksFor($user)
            ->whereDate('due_date', '<', $today)
            ->orderBy('due_date')
            ->get();

        if ($overdue->isNotEmpty()) {
            $sections['scaduti'] = $overdue->map(fn($t) => [
                'id'    => $t->id,
                'title' => $t->title,
                'area'  => $t->area?->name,
            ])->all();
            $taskCount += $overdue->count();
        }

        foreach ($thresholds as $days) {
            $targetDate = $today->copy()->addDays($days);
            $tasks = $this->openTasksFor($user)
                ->whereDate('due_date', $targetDate)
                ->orderBy('due_date')
                ->get();

            if ($tasks->isNotEmpty()) {
                $key = self::THRESHOLD_KEYS[$days] ?? "{$days}giorni";
                $sections[$key] = $tasks->map(fn($t) => [
                    'id'    => $t->id,
                    'title' => $t->title,
                    'area'  => $t->area?->name,
                ])->all();
                $taskCount += $tasks->count();
            }
        }

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
                // Mnemosyne down: digest parziale, nessun retry
            }
        }

        if ($taskCount === 0 && empty($dormant)) return;

        Mail::to($user->email)->send(new DailyDigest(
            sections: $sections,
            dormant:  $dormant,
            userName: $user->name,
        ));

        NotificationLog::create([
            'user_id' => $user->id,
            'type'    => 'digest',
            'payload' => [
                'tasks_count'   => $taskCount,
                'dormant_count' => count($dormant),
                'mnemosyne_ok'  => $mnemoOk,
            ],
        ]);
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
