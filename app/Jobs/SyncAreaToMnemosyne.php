<?php

namespace App\Jobs;

use App\Models\Area;
use App\Services\MnemosyneService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncAreaToMnemosyne implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $areaId) {}

    public function backoff(): array
    {
        return [60, 300, 900, 1800, 3600];
    }

    public function handle(MnemosyneService $mnemosyne): void
    {
        if (!$mnemosyne->enabled()) {
            return;
        }

        $area = Area::with('parent')->find($this->areaId);
        if (!$area) {
            return;
        }

        $relations = [];
        if ($area->parent) {
            $relations[] = $area->parent->mnemosyneName() . ':PART_OF';
        }

        $content = trim($area->description ?: $area->name);

        $response = $mnemosyne->pushNode(
            $area->mnemosyneName(),
            $content,
            'Area',
            implode(',', $relations),
            'Private',
        );

        $name = $response['name'] ?? null;
        if ($name && $name !== $area->mnemosyne_node_name) {
            $area->forceFill(['mnemosyne_node_name' => $name])->saveQuietly();
        }
    }
}
