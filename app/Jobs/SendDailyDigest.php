<?php

namespace App\Jobs;

use App\Mail\DailyDigest;
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

    public function handle(MnemosyneService $mnemosyne): void
    {
        $users = User::all();

        foreach ($users as $user) {
            try {
                $this->sendForUser($user, $mnemosyne);
            } catch (\Throwable $e) {
                Log::error("SendDailyDigest: errore per user {$user->id}: {$e->getMessage()}");
            }
        }
    }

    private function sendForUser(User $user, MnemosyneService $mnemosyne): void
    {
        // Legge preferenze
        $enabled    = Setting::get('digest.enabled', '1') === '1';
        $thresholds = json_decode(Setting::get('digest.thresholds', json_encode([0, 1, 3, 7, 30])), true);
        $withMnemo  = Setting::get('digest.mnemosyne', '1') === '1';

        if (!$enabled) return;

        $today = Carbon::today();

        // Raccoglie task per soglia
        $sections   = [];
        $taskCount  = 0;

        foreach ($thresholds as $days) {
            $targetDate = $today->copy()->addDays($days);
            $tasks = Task::with('area')
                ->whereNull('deleted_at')
                ->whereDate('due_date', $targetDate)
                ->whereNotIn('stage_id', $this->doneStageIds())
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhere('assigned_to', $user->id)
                      ->orWhereHas('collaborators', fn($c) => $c->where('user_id', $user->id));
                })
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

        // Dormienti Mnemosyne
        $dormant    = [];
        $mnemoOk    = false;

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

        // Niente da dire
        if ($taskCount === 0 && empty($dormant)) return;

        // Invia
        Mail::to($user->email)->send(new DailyDigest(
            sections:  $sections,
            dormant:   $dormant,
            userName:  $user->name,
        ));

        // Log
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

    private function doneStageIds(): array
    {
        return \App\Models\Stage::whereIn('code', ['done', 'archiviato'])->pluck('id')->all();
    }

    private function buildDormant(array $nodes): array
    {
        $doneIds = $this->doneStageIds();
        $result  = [];

        foreach (array_slice($nodes, 0, 5) as $node) {
            $name  = $node['name'] ?? '';
            $label = $node['properties']['label'] ?? $name;

            // Cerca il task corrispondente dal mnemosyne_node_name
            $task = Task::where('mnemosyne_node_name', $name)
                ->whereNotIn('stage_id', $doneIds)
                ->whereNull('deleted_at')
                ->first();

            // Salta se già completato/archiviato (ma non trovato sopra)
            if ($task === null && str_contains($name, '-')) {
                continue; // nodo non riconducibile a un task attivo
            }

            $daysInactive = $task
                ? (int) $task->updated_at->diffInDays(now())
                : ($node['days_inactive'] ?? 0);

            $result[] = [
                'label'        => $task ? $task->title : $label,
                'task_id'      => $task?->id,
                'days_inactive'=> $daysInactive,
            ];
        }

        return $result;
    }
}
