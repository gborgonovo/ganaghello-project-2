<?php

namespace App\Jobs;

use App\Services\MnemosyneService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteMnemosyneNode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public string $nodeName, public string $scope = 'Private') {}

    public function backoff(): array
    {
        return [60, 300, 900, 1800, 3600];
    }

    public function handle(MnemosyneService $mnemosyne): void
    {
        if (!$mnemosyne->enabled()) {
            return;
        }

        $mnemosyne->deleteNode($this->nodeName, $this->scope);
    }
}
