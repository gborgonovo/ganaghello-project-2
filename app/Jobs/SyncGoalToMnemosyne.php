<?php

namespace App\Jobs;

use App\Models\Goal;
use App\Services\MnemosyneService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncGoalToMnemosyne implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public int $goalId) {}

    public function backoff(): array
    {
        return [60, 300, 900, 1800, 3600];
    }

    public function handle(MnemosyneService $mnemosyne): void
    {
        if (!$mnemosyne->enabled()) {
            return;
        }

        $goal = Goal::with('parent')->find($this->goalId);
        if (!$goal) {
            return;
        }

        $relations = [];
        if ($goal->parent) {
            $relations[] = $goal->parent->mnemosyneName() . ':PART_OF';
        }

        $response = $mnemosyne->pushGoal(
            $goal->mnemosyneName(),
            (string) $goal->description,
            $goal->deadline?->toDateString(),
            implode(',', $relations),
        );

        $name = $response['name'] ?? null;
        if ($name && $name !== $goal->mnemosyne_node_name) {
            $goal->forceFill(['mnemosyne_node_name' => $name])->saveQuietly();
        }
    }
}
