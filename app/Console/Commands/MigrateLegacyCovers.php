<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use App\Models\Post;
use App\Services\MediaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Converte le copertine "legacy" dei post (campo `cover` con URL/path verso
 * /storage/images/...) in media veri serviti da media.serve, allineandoli al
 * resto dell'app. Per ogni post con cover legacy e senza allegati: ingestisce
 * il file da storage/app/public, crea un Attachment (sequence 1) e azzera cover.
 * Idempotente: i post che hanno gia' un allegato vengono saltati.
 *
 * Prerequisito: i file v1 devono essere presenti in storage/app/public/images.
 */
class MigrateLegacyCovers extends Command
{
    protected $signature = 'blog:migrate-legacy-covers {--dry-run : Mostra cosa farebbe senza scrivere nulla}';

    protected $description = 'Converte le copertine legacy dei post in media allegati (media.serve)';

    public function handle(MediaService $media): int
    {
        $dry = (bool) $this->option('dry-run');

        $posts = Post::whereNotNull('cover')->where('cover', '!=', '')
            ->whereDoesntHave('attachments')
            ->get();

        if ($posts->isEmpty()) {
            $this->info('Nessuna copertina legacy da migrare.');
            return self::SUCCESS;
        }

        $migrated = $skipped = $errors = 0;

        foreach ($posts as $post) {
            $rel = $this->relativePath($post->cover);

            if (!$rel || !Storage::disk('public')->exists($rel)) {
                $this->warn("Post #{$post->id}: file mancante per cover \"{$post->cover}\" (salto).");
                $errors++;
                continue;
            }

            $absPath = Storage::disk('public')->path($rel);
            $name    = basename($rel);

            if ($dry) {
                $this->line("Post #{$post->id}: {$rel} -> media (dry-run).");
                $migrated++;
                continue;
            }

            try {
                $m = $media->storeFromPath($absPath, $name, (int) $post->user_id, 'blog');

                Attachment::create([
                    'attachable_type' => Post::class,
                    'attachable_id'   => $post->id,
                    'media_id'        => $m->id,
                    'sequence'        => 1,
                ]);

                // Azzera la cover legacy senza svegliare observer/sync Mnemosyne.
                $post->cover = null;
                $post->saveQuietly();

                $migrated++;
            } catch (\Throwable $e) {
                $this->error("Post #{$post->id}: errore ({$e->getMessage()}).");
                $errors++;
            }
        }

        $this->newLine();
        $this->info("Migrate: {$migrated} · saltate: {$skipped} · errori: {$errors}" . ($dry ? ' (dry-run)' : ''));

        return self::SUCCESS;
    }

    /** Da un cover legacy (URL assoluto o path) ricava il path relativo al disco public. */
    private function relativePath(string $cover): ?string
    {
        $path = parse_url($cover, PHP_URL_PATH) ?: $cover;   // /storage/images/x.jpg | images/x.jpg
        $path = ltrim($path, '/');                            // storage/images/x.jpg | images/x.jpg
        $path = preg_replace('#^storage/#', '', $path);       // images/x.jpg

        return $path !== '' ? rawurldecode($path) : null;
    }
}
