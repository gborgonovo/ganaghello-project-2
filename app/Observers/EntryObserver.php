<?php

namespace App\Observers;

use App\Jobs\DeleteMnemosyneNode;
use App\Jobs\SyncEntryToMnemosyne;
use App\Models\Entry;

class EntryObserver
{
    private const SEMANTIC = ['title', 'content', 'area_id', 'entry_date'];

    public function created(Entry $entry): void
    {
        $this->sync($entry);
    }

    public function updated(Entry $entry): void
    {
        if ($entry->wasChanged(self::SEMANTIC)) {
            $this->sync($entry);
        }
    }

    public function deleted(Entry $entry): void
    {
        if ($entry->mnemosyne_node_name) {
            DeleteMnemosyneNode::dispatch($entry->mnemosyne_node_name);
        }
    }

    public function restored(Entry $entry): void
    {
        $this->sync($entry);
    }

    private function sync(Entry $entry): void
    {
        if (config('services.mnemosyne.sync', true)) {
            SyncEntryToMnemosyne::dispatch($entry->id);
        }
    }
}
