<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use App\Models\Post;
use App\Services\MediaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class LocalizeBlogImages extends Command
{
    protected $signature   = 'blog:localize-images {--force : Riscarica anche i post che hanno gia una copertina locale}';
    protected $description = 'Scarica in locale le immagini esterne dei post importati (cover e immagini nel corpo), per non dipendere dalla v1.';

    public function handle(MediaService $media): int
    {
        $posts = Post::with('attachments')->get();

        $covers = ['ok' => 0, 'skip' => 0, 'err' => 0];
        $bodies = ['ok' => 0, 'err' => 0];

        foreach ($posts as $post) {
            // --- COPERTINA ---
            if ($this->isExternal($post->cover)) {
                if ($post->hasLocalCover() && !$this->option('force')) {
                    $covers['skip']++;
                } else {
                    $localMedia = $this->downloadToMedia($media, $post->cover, $post->user_id);
                    if ($localMedia) {
                        Attachment::create([
                            'attachable_type' => Post::class,
                            'attachable_id'   => $post->id,
                            'media_id'        => $localMedia->id,
                            'sequence'        => 0,
                        ]);
                        // La stringa cover diventa irrilevante: la svuoto
                        $post->update(['cover' => null]);
                        $covers['ok']++;
                        $this->line("  <info>cover</info> post #{$post->id} → media #{$localMedia->id}");
                    } else {
                        $covers['err']++;
                        $this->line("  <error>cover KO</error> post #{$post->id}: {$post->cover}");
                    }
                }
            } else {
                $covers['skip']++;
            }

            // --- IMMAGINI NEL CORPO ---
            $content = $post->content;
            $newContent = preg_replace_callback(
                '/(https?:\/\/[^\s")\']+\.(?:jpg|jpeg|png|gif|webp))/i',
                function ($m) use ($media, $post, &$bodies) {
                    $localMedia = $this->downloadToMedia($media, $m[1], $post->user_id);
                    if ($localMedia) {
                        // Allego il media al post (necessario per il serving filtrato)
                        Attachment::create([
                            'attachable_type' => Post::class,
                            'attachable_id'   => $post->id,
                            'media_id'        => $localMedia->id,
                            'sequence'        => 100, // dopo la cover
                        ]);
                        $bodies['ok']++;
                        return route('storie.img', ['media' => $localMedia->id, 'size' => 'medium']);
                    }
                    $bodies['err']++;
                    return $m[1]; // lascia l'URL originale se fallisce
                },
                $content
            );

            if ($newContent !== $content) {
                $post->update(['content' => $newContent]);
                $this->line("  <info>corpo</info> post #{$post->id}: immagini riscritte");
            }
        }

        $this->newLine();
        $this->info("Copertine: {$covers['ok']} localizzate, {$covers['skip']} saltate, {$covers['err']} errori.");
        $this->info("Immagini nel corpo: {$bodies['ok']} localizzate, {$bodies['err']} errori.");

        return self::SUCCESS;
    }

    private function isExternal(?string $value): bool
    {
        return $value && str_starts_with($value, 'http');
    }

    /**
     * Scarica un URL in un temporaneo e lo salva via MediaService.
     * Se l'URL e' un /thumbs/, tenta prima il full-size togliendo /thumbs/.
     * Tollerante ai fallimenti: ritorna null in caso di errore.
     */
    private function downloadToMedia(MediaService $media, string $url, int $userId)
    {
        $candidates = [];
        if (str_contains($url, '/thumbs/')) {
            $candidates[] = str_replace('/thumbs/', '/', $url); // full-size
        }
        $candidates[] = $url;

        foreach ($candidates as $candidate) {
            try {
                $resp = Http::timeout(20)->get($candidate);
                if (!$resp->successful()) {
                    continue;
                }
                $body = $resp->body();
                if (strlen($body) < 100) {
                    continue; // troppo piccolo per essere un'immagine valida
                }

                $tmp = tempnam(sys_get_temp_dir(), 'blogimg_');
                file_put_contents($tmp, $body);

                $name = basename(parse_url($candidate, PHP_URL_PATH)) ?: 'cover.jpg';

                try {
                    $m = $media->storeFromPath($tmp, $name, $userId);
                    @unlink($tmp);
                    return $m;
                } catch (\Throwable $e) {
                    @unlink($tmp);
                    $this->line("    <comment>processing fallito</comment> {$candidate}: {$e->getMessage()}");
                    continue;
                }
            } catch (\Throwable $e) {
                $this->line("    <comment>download fallito</comment> {$candidate}: {$e->getMessage()}");
                continue;
            }
        }

        return null;
    }
}
