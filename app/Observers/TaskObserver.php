<?php

namespace App\Observers;

use App\Jobs\DeleteMnemosyneNode;
use App\Jobs\SyncTaskToMnemosyne;
use App\Models\Task;

class TaskObserver
{
    /** Campi semantici: solo questi giustificano una risincronizzazione. */
    private const SEMANTIC = ['title', 'description', 'due_date', 'area_id', 'parent_task_id'];

    public function created(Task $task): void
    {
        $this->sync($task);
    }

    public function updated(Task $task): void
    {
        if ($task->wasChanged(self::SEMANTIC)) {
            $this->sync($task);
        }
    }

    public function deleted(Task $task): void
    {
        if ($task->mnemosyne_node_name) {
            DeleteMnemosyneNode::dispatch($task->mnemosyne_node_name);
        }
    }

    public function restored(Task $task): void
    {
        $this->sync($task);
    }

    private function sync(Task $task): void
    {
        if (config('services.mnemosyne.sync', true)) {
            SyncTaskToMnemosyne::dispatch($task->id);
        }
    }
}
