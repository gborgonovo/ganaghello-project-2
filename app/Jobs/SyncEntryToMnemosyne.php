<?php

namespace App\Jobs;

use App\Models\Entry;
use App\Services\MnemosyneService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncEntryToMnemosyne implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $entryId) {}

    public function backoff(): array
    {
        return [60, 300, 900, 1800, 3600];
    }

    public function handle(MnemosyneService $mnemosyne): void
    {
        if (!$mnemosyne->enabled()) {
            return;
        }

        $entry = Entry::with(['area', 'tasks'])->find($this->entryId);
        if (!$entry) {
            return;
        }

        $relations = [];
        if ($entry->area) {
            $relations[] = $entry->area->mnemosyneName() . ':ABOUT';
        }
        foreach ($entry->tasks as $task) {
            $relations[] = $task->mnemosyneName() . ':DOCUMENTS';
        }

        $content = trim(($entry->title ? "# {$entry->title}\n\n" : '') . $entry->content);
        if ($content === '') {
            // Voce senza testo (es. solo foto): Mnemosyne richiede `content` non vuoto,
            // e write() scarta le stringhe vuote. Diamo un contenuto minimo con la data.
            $date = $entry->entry_date?->format('d/m/Y');
            $content = 'Voce di diario' . ($date ? " del {$date}" : '') . '.';
        }

        $response = $mnemosyne->pushNode(
            $entry->mnemosyneName(),
            $content,
            'Journal',
            implode(',', $relations),
            'Private', // il diario e' sempre privato; il pubblico vive nei Post (blog)
        );

        $name = $response['name'] ?? null;
        if ($name && $name !== $entry->mnemosyne_node_name) {
            $entry->forceFill(['mnemosyne_node_name' => $name])->saveQuietly();
        }
    }
}
