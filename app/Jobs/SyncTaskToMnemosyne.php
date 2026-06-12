<?php

namespace App\Jobs;

use App\Models\Task;
use App\Services\MnemosyneService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncTaskToMnemosyne implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $taskId) {}

    public function backoff(): array
    {
        return [60, 300, 900, 1800, 3600];
    }

    public function handle(MnemosyneService $mnemosyne): void
    {
        if (!$mnemosyne->enabled()) {
            return;
        }

        $task = Task::with(['parent', 'area', 'goals'])->find($this->taskId);
        if (!$task) {
            return;
        }

        $relations = [];
        if ($task->parent) {
            $relations[] = $task->parent->mnemosyneName() . ':PART_OF';
        }
        foreach ($task->goals as $goal) {
            $relations[] = $goal->mnemosyneName() . ':CONTRIBUTES_TO';
        }
        if ($task->area) {
            $relations[] = $task->area->mnemosyneName() . ':LOCATED_IN';
        }

        $description = (string) $task->description;
        if ($task->isDone()) {
            $description = trim($description . "\n\n(stato: completato)");
        }

        $response = $mnemosyne->pushTask(
            $task->mnemosyneName(),
            $description,
            $task->due_date?->toDateString(),
            implode(',', $relations),
        );

        $this->persistCanonicalName($task, $response);
    }

    private function persistCanonicalName(Task $task, array $response): void
    {
        $name = $response['name'] ?? null;
        if ($name && $name !== $task->mnemosyne_node_name) {
            $task->forceFill(['mnemosyne_node_name' => $name])->saveQuietly();
        }
    }
}
