<?php

namespace App\Observers;

use App\Jobs\DeleteMnemosyneNode;
use App\Jobs\SyncGoalToMnemosyne;
use App\Models\Goal;

class GoalObserver
{
    private const SEMANTIC = ['title', 'description', 'deadline', 'status', 'parent_goal_id'];

    public function created(Goal $goal): void
    {
        $this->sync($goal);
    }

    public function updated(Goal $goal): void
    {
        if ($goal->wasChanged(self::SEMANTIC)) {
            $this->sync($goal);
        }
    }

    public function deleted(Goal $goal): void
    {
        if ($goal->mnemosyne_node_name) {
            DeleteMnemosyneNode::dispatch($goal->mnemosyne_node_name);
        }
    }

    public function restored(Goal $goal): void
    {
        $this->sync($goal);
    }

    private function sync(Goal $goal): void
    {
        if (config('services.mnemosyne.sync', true)) {
            SyncGoalToMnemosyne::dispatch($goal->id);
        }
    }
}
