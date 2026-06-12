<?php

namespace App\Jobs;

use App\Models\Attachment;
use App\Models\Inspiration;
use App\Services\MediaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LocalizeInspirationCover implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(public readonly int $inspirationId) {}

    public function handle(MediaService $mediaService): void
    {
        $insp = Inspiration::find($this->inspirationId);

        // Già localizzata o eliminata
        if (!$insp || !$insp->cover) return;
        if ($insp->attachments()->exists()) return;

        $url = $insp->cover;

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; Ganaghello/1.0)',
            ])->timeout(20)->get($url);

            if (!$response->successful()) {
                Log::info("LocalizeInspirationCover: download fallito [{$response->status()}] per inspiration {$this->inspirationId}");
                return;
            }

            $contentType = $response->header('Content-Type') ?? 'image/jpeg';
            if (!str_starts_with($contentType, 'image/')) {
                Log::info("LocalizeInspirationCover: risposta non immagine per inspiration {$this->inspirationId}");
                return;
            }

            $tmpPath = sys_get_temp_dir() . '/' . Str::uuid() . '.jpg';
            file_put_contents($tmpPath, $response->body());

            $media = $mediaService->storeFromPath(
                $tmpPath,
                basename(parse_url($url, PHP_URL_PATH)) ?: 'cover.jpg',
                $insp->user_id,
                'inspiration',
            );

            @unlink($tmpPath);

            Attachment::create([
                'attachable_type' => Inspiration::class,
                'attachable_id'   => $insp->id,
                'media_id'        => $media->id,
                'sequence'        => 1,
            ]);

            $insp->forceFill(['cover' => null])->saveQuietly();

        } catch (\Throwable $e) {
            Log::warning("LocalizeInspirationCover: eccezione per inspiration {$this->inspirationId}: {$e->getMessage()}");
            // Non rilancia: i retry si attivano solo se lanciamo l'eccezione.
            // Per i siti con hotlink protection non ha senso ritentare.
        }
    }
}
