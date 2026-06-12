<?php

namespace App\Observers;

use App\Jobs\DeleteMnemosyneNode;
use App\Jobs\SyncAreaToMnemosyne;
use App\Models\Area;

class AreaObserver
{
    private const SEMANTIC = ['name', 'description', 'parent_area_id'];

    public function created(Area $area): void
    {
        $this->sync($area);
    }

    public function updated(Area $area): void
    {
        if ($area->wasChanged(self::SEMANTIC)) {
            $this->sync($area);
        }
    }

    public function deleted(Area $area): void
    {
        if ($area->mnemosyne_node_name) {
            DeleteMnemosyneNode::dispatch($area->mnemosyne_node_name);
        }
    }

    private function sync(Area $area): void
    {
        if (config('services.mnemosyne.sync', true)) {
            SyncAreaToMnemosyne::dispatch($area->id);
        }
    }
}
