<?php

namespace App\Console\Commands;

use App\Models\Area;
use App\Models\Attachment;
use App\Models\Entry;
use App\Models\Inspiration;
use App\Models\Media;
use App\Models\Note;
use App\Models\Post;
use App\Models\Stage;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use PDO;

class MigrateFromV1 extends Command
{
    protected $signature   = 'migrate:from-v1 {--fresh : Elimina task/note/ispirazione/media prima di importare}';
    protected $description = 'Importa dati da ganaghello-project v1 (SQLite)';

    private const V1_DB  = '/srv/http/ganaghello-project/database/ganaghello-project.sqlite';
    private const V1_IMG = '/srv/http/ganaghello-project/storage/app/public/images';

    // v1 category_id => [type, v2_name, v2_parent_name|null]
    private const CAT_MAP = [
        1  => ['area', 'Giardino',              null],
        2  => ['tag',  'impianti',              null],
        3  => ['tag',  'comunicazione',         null],
        4  => ['tag',  'struttura',             null],
        6  => ['area', 'Annessi',               null],
        7  => ['area', 'Stalla',                null],
        8  => ['area', 'Silos',                 null],
        9  => ['tag',  'documenti',             null],
        10 => ['tag',  'attrezzi',              null],
        11 => ['tag',  'business',              null],
        12 => ['area', 'Abitazione',            null],
        13 => ['area', 'Magazzino',             'Annessi'],
        14 => ['area', 'Camera da letto Ovest', 'Abitazione'],
        15 => ['area', 'Soggiorno Ovest',       'Abitazione'],
        17 => ['area', 'Cucina Ovest',          'Abitazione'],
        18 => ['area', 'Bagno Ovest',           'Abitazione'],
        27 => ['area', 'Stalletta',             'Annessi'],
        35 => ['area', 'Prato',                 null],
    ];

    // v1 stage_id => v2 code (completed_on sovrascrive tutto con 'done')
    private const STAGE_MAP = [
        0 => 'idea',
        1 => 'doing',
        2 => 'todo',
        3 => 'approvato',
        4 => 'todo',
        5 => 'idea',
    ];

    private PDO   $v1;
    private int   $userId;
    private array $areaIdMap  = []; // v1_cat_id => v2 area id
    private array $tagIdMap   = []; // v1_cat_id => v2 tag id
    private array $stageCache = []; // code => stage id
    private array $taskIdMap  = []; // v1 item id => v2 task id

    public function handle(): int
    {
        // Import massivo: niente sync verso Mnemosyne (si fa dopo con mnemosyne:sync-all).
        config(['services.mnemosyne.sync' => false]);

        if (!file_exists(self::V1_DB)) {
            $this->error('DB v1 non trovato: ' . self::V1_DB);
            return 1;
        }

        $this->v1     = new PDO('sqlite:' . self::V1_DB);
        $this->userId = User::first()?->id ?? 1;

        if ($this->option('fresh')) {
            $this->freshWipe();
        } elseif (Task::count() > 0) {
            $this->warn('Ci sono già task nel DB v2. Usa --fresh per sovrascrivere.');
            return 1;
        }

        $this->buildStageCache();

        DB::transaction(function () {
            $this->createAreas();
            $this->createTags();
            $this->importTasks();
            $this->importNotes();
            $this->importInspirations();
            $this->importPosts();
            $this->importPrivateAsEntries();
        });

        $this->newLine();
        $this->info('Migrazione completata.');
        return 0;
    }

    // -----------------------------------------------------------------------

    private function freshWipe(): void
    {
        $this->warn('Pulizia dati esistenti...');
        DB::table('taggables')->delete();
        DB::table('attachments')->delete();
        DB::table('task_updates')->delete();
        DB::table('goal_task')->delete();
        DB::table('entry_post')->delete();
        Task::withTrashed()->forceDelete();
        Note::withTrashed()->forceDelete();
        Inspiration::withTrashed()->forceDelete();
        Post::withTrashed()->forceDelete();
        Entry::withTrashed()->forceDelete();
        Media::all()->each(fn ($m) => app(MediaService::class)->delete($m));
        Storage::disk('private')->deleteDirectory('media');
        $this->warn('File media fisici rimossi.');
    }

    private function buildStageCache(): void
    {
        $this->stageCache = Stage::pluck('id', 'code')->toArray();
    }

    private function stageId(string $code): ?int
    {
        return $this->stageCache[$code] ?? null;
    }

    // -----------------------------------------------------------------------
    // AREE

    private function createAreas(): void
    {
        $this->info('Creazione aree...');

        // Prima passata: aree di primo livello (parent null)
        foreach (self::CAT_MAP as $catId => [$type, $name, $parent]) {
            if ($type !== 'area' || $parent !== null) continue;
            $area = Area::firstOrCreate(['name' => $name], [
                'status'        => 'attivo',
                'parent_area_id'=> null,
            ]);
            $this->areaIdMap[$catId] = $area->id;
            $this->line("  area: $name (id {$area->id})");
        }

        // Seconda passata: sub-aree
        foreach (self::CAT_MAP as $catId => [$type, $name, $parent]) {
            if ($type !== 'area' || $parent === null) continue;
            $parentArea = Area::where('name', $parent)->first();
            $area = Area::firstOrCreate(['name' => $name], [
                'status'        => 'attivo',
                'parent_area_id'=> $parentArea?->id,
            ]);
            $this->areaIdMap[$catId] = $area->id;
            $this->line("  sub-area: $name → $parent (id {$area->id})");
        }
    }

    // -----------------------------------------------------------------------
    // TAG

    private function createTags(): void
    {
        $this->info('Creazione tag...');

        foreach (self::CAT_MAP as $catId => [$type, $name]) {
            if ($type !== 'tag') continue;
            $tag = Tag::firstOrCreate(['name' => strtolower($name)]);
            $this->tagIdMap[$catId] = $tag->id;
            $this->line("  tag: $name (id {$tag->id})");
        }
    }

    // -----------------------------------------------------------------------
    // TASK

    private function importTasks(): void
    {
        $this->info('Importazione task...');

        $items = $this->v1->query(
            "SELECT * FROM items WHERE type='task' AND deleted_at IS NULL ORDER BY id"
        )->fetchAll(PDO::FETCH_ASSOC);

        // Prima passata: crea tutti i task senza parent_task_id
        foreach ($items as $item) {
            [$areaId, $tagCatIds] = $this->resolveAreaAndTags((int) $item['id']);

            $stageCode = $this->resolveStageCode($item);
            $stageId   = $this->stageId($stageCode);

            $task = Task::create([
                'user_id'      => $this->userId,
                'title'        => $item['title'],
                'description'  => $item['description'] ?? null,
                'stage_id'     => $stageId,
                'area_id'      => $areaId,
                'due_date'     => $item['due_date'] ? date('Y-m-d', strtotime($item['due_date'])) : null,
                'completed_at' => $item['completed_on'] ?? null,
                'cost_min'     => $item['cost_min'] ?? null,
                'cost_max'     => $item['cost_max'] ?? null,
                'cost_real'    => $item['cost_real'] ?? null,
            ]);

            $this->taskIdMap[(int) $item['id']] = $task->id;

            // Tag
            if ($tagCatIds) {
                $tagIds = array_filter(array_map(fn ($c) => $this->tagIdMap[$c] ?? null, $tagCatIds));
                if ($tagIds) {
                    DB::table('taggables')->insert(
                        array_map(fn ($tid) => [
                            'tag_id'         => $tid,
                            'taggable_id'    => $task->id,
                            'taggable_type'  => Task::class,
                        ], array_values($tagIds))
                    );
                }
            }

            // Cover
            if (!empty($item['cover'])) {
                $this->importCover($item['cover'], $task);
            }

            $this->line("  task [{$item['id']}] → {$task->id}: {$task->title}");
        }

        // Seconda passata: imposta parent_task_id
        foreach ($items as $item) {
            if (empty($item['parent_id'])) continue;
            $parentV2 = $this->taskIdMap[(int) $item['parent_id']] ?? null;
            $childV2  = $this->taskIdMap[(int) $item['id']] ?? null;
            if ($parentV2 && $childV2) {
                Task::where('id', $childV2)->update(['parent_task_id' => $parentV2]);
            }
        }

        $total = count($items);
        $this->info("  Importati $total task.");
    }

    // -----------------------------------------------------------------------
    // NOTE

    private function importNotes(): void
    {
        $this->info('Importazione note...');

        $items = $this->v1->query(
            "SELECT * FROM items WHERE type='note' AND deleted_at IS NULL ORDER BY id"
        )->fetchAll(PDO::FETCH_ASSOC);

        $count = 0;
        foreach ($items as $item) {
            [$areaId] = $this->resolveAreaAndTags((int) $item['id']);

            Note::create([
                'user_id' => $this->userId,
                'area_id' => $areaId,
                'title'   => $item['title'] ?: null,
                'content' => $item['description'] ?? '',
            ]);
            $count++;
        }

        $this->info("  Importate $count note.");
    }

    // -----------------------------------------------------------------------
    // ISPIRAZIONI

    private function importInspirations(): void
    {
        $this->info('Importazione ispirazioni...');

        $items = $this->v1->query(
            "SELECT * FROM items WHERE type='mood' AND deleted_at IS NULL ORDER BY id"
        )->fetchAll(PDO::FETCH_ASSOC);

        $count = 0;
        $skipped = 0;

        foreach ($items as $item) {
            $hasTitle = !empty(trim($item['title'] ?? ''));
            $hasCover = !empty(trim($item['cover'] ?? ''));

            if (!$hasTitle && !$hasCover) {
                $this->line("  skip mood [{$item['id']}]: no title, no cover");
                $skipped++;
                continue;
            }

            [$areaId] = $this->resolveAreaAndTags((int) $item['id']);

            $insp = Inspiration::create([
                'user_id'     => $this->userId,
                'area_id'     => $areaId,
                'title'       => $item['title'] ?: null,
                'description' => $item['description'] ?? null,
                'url'         => $item['link'] ?: null,
            ]);

            if ($hasCover) {
                if (str_starts_with($item['cover'], 'http')) {
                    // URL esterna: mantieni come link nel campo cover, non scaricare
                    $insp->forceFill(['cover' => $item['cover']])->saveQuietly();
                } else {
                    // File locale: scarica e localizza come attachment
                    $this->importCover($item['cover'], $insp);
                }
            }

            $count++;
        }

        $this->info("  Importate $count ispirazioni, saltate $skipped.");
    }

    // -----------------------------------------------------------------------
    // POST PUBBLICI E BOZZE

    private function importPosts(): void
    {
        $this->info('Importazione post...');

        $rows = $this->v1->query(
            "SELECT * FROM posts WHERE is_published = 1 AND is_private = 0 AND deleted_at IS NULL ORDER BY id"
        )->fetchAll(PDO::FETCH_ASSOC);

        $count = 0;
        $slugs = [];

        foreach ($rows as $row) {
            $visibility = $row['is_published'] ? 'public' : 'draft';
            $slug       = $this->uniqueSlug($row['title'], $slugs);
            $slugs[]    = $slug;

            Post::create([
                'user_id'      => $this->userId,
                'title'        => $row['title'],
                'slug'         => $slug,
                'excerpt'      => $row['excerpt'] ?: null,
                'cover'        => $row['image'] ?: null,
                'content'      => $row['content'],
                'visibility'   => $visibility,
                'published_at' => $row['published_at'] ?: ($row['is_published'] ? $row['created_at'] : null),
            ]);

            $count++;
        }

        $this->info("  Importati $count post.");
    }

    // -----------------------------------------------------------------------
    // POST PRIVATI → ENTRY DIARIO

    private function importPrivateAsEntries(): void
    {
        $this->info('Importazione post non pubblici come entry diario...');

        $rows = $this->v1->query(
            "SELECT * FROM posts WHERE (is_published = 0 OR is_private = 1) AND deleted_at IS NULL ORDER BY id"
        )->fetchAll(PDO::FETCH_ASSOC);

        $count = 0;

        foreach ($rows as $row) {
            $entryDate = $row['published_at'] ?: $row['created_at'];
            $entryDate = $entryDate ? date('Y-m-d', strtotime($entryDate)) : today()->toDateString();

            Entry::create([
                'user_id'    => $this->userId,
                'title'      => $row['title'] ?: null,
                'content'    => trim($row['content']),
                'entry_date' => $entryDate,
            ]);

            $count++;
        }

        $this->info("  Importate $count entry diario.");
    }

    // -----------------------------------------------------------------------
    // HELPER: genera slug unico

    private function uniqueSlug(string $title, array $existing): string
    {
        $base = Str::slug($title);
        if (!$base) $base = 'post';
        $slug = $base;
        $i    = 2;
        while (in_array($slug, $existing) || Post::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }

    // -----------------------------------------------------------------------
    // HELPER: risolve area_id e tag_cat_ids da category_item

    private function resolveAreaAndTags(int $itemId): array
    {
        $rows = $this->v1->prepare(
            'SELECT category_id FROM category_item WHERE item_id = ? ORDER BY id'
        );
        $rows->execute([$itemId]);
        $catIds = $rows->fetchAll(PDO::FETCH_COLUMN);

        $areaId    = null;
        $tagCatIds = [];
        $extraAreaCatIds = [];

        foreach ($catIds as $catId) {
            $def = self::CAT_MAP[$catId] ?? null;
            if (!$def) continue; // categoria non mappata

            [$type] = $def;

            if ($type === 'area') {
                if ($areaId === null) {
                    $areaId = $this->areaIdMap[$catId] ?? null;
                } else {
                    // area extra → diventa tag (nome area in minuscolo come tag)
                    $extraAreaCatIds[] = $catId;
                }
            } else {
                $tagCatIds[] = (int) $catId;
            }
        }

        return [$areaId, $tagCatIds];
    }

    // -----------------------------------------------------------------------
    // HELPER: stage mapping

    private function resolveStageCode(array $item): string
    {
        if (!empty($item['completed_on'])) {
            return 'done';
        }
        return self::STAGE_MAP[(int) ($item['stage_id'] ?? 0)] ?? 'idea';
    }

    // -----------------------------------------------------------------------
    // HELPER: copia cover locale in Media v2

    private const V1_BASE_URL = 'https://ganaghello.borgonovo.org';

    private function importCover(string $cover, $entity): void
    {
        // Determina la sorgente dei dati immagine
        if (str_starts_with($cover, 'http')) {
            // Qualsiasi URL HTTP: tenta il download
            $imageData    = $this->fetchUrl($cover);
            $originalName = basename(parse_url($cover, PHP_URL_PATH));
            if ($imageData === null) {
                // Download fallito: salva l'URL come fallback solo se il modello ha il campo cover
                if (in_array('cover', $entity->getFillable())) {
                    $entity->forceFill(['cover' => $cover])->saveQuietly();
                }
                $this->warn("  cover non scaricabile, salvata come URL: " . Str::limit($cover, 60));
                return;
            }
        } else {
            // Filename: prova prima in locale, poi scarica da produzione
            $srcPath = self::V1_IMG . '/' . $cover;
            if (file_exists($srcPath)) {
                $imageData = file_get_contents($srcPath);
            } else {
                $remoteUrl = self::V1_BASE_URL . '/storage/images/' . rawurlencode($cover);
                $imageData = $this->fetchUrl($remoteUrl);
            }
            $originalName = $cover;
        }

        if ($imageData === null) {
            $this->warn("  cover non disponibile: " . Str::limit($cover, 60));
            return;
        }

        try {
            $uuid     = Str::uuid()->toString();
            $folder   = now()->format('Y/m');
            $basePath = "media/{$folder}/{$uuid}";

            $manager = new ImageManager(new Driver());
            $image   = $manager->read($imageData);

            $sizes = ['thumb' => 400, 'medium' => 1400, 'original' => 3000];
            foreach ($sizes as $sizeName => $maxPx) {
                $img = clone $image;
                if ($img->width() > $maxPx || $img->height() > $maxPx) {
                    $img->scaleDown($maxPx, $maxPx);
                }
                $quality = match($sizeName) { 'thumb' => 80, 'medium' => 85, default => 90 };
                Storage::disk('private')->put("{$basePath}/{$sizeName}", (string) $img->toJpeg($quality));
            }

            $media = Media::create([
                'user_id'           => $this->userId,
                'filename'          => $uuid,
                'original_filename' => $originalName,
                'path'              => "{$basePath}/original",
                'folder'            => $folder,
                'mime_type'         => 'image/jpeg',
                'size'              => strlen($imageData),
            ]);

            Attachment::create([
                'attachable_type' => get_class($entity),
                'attachable_id'   => $entity->id,
                'media_id'        => $media->id,
                'sequence'        => 1,
            ]);

            $this->line("  cover importata: $originalName → media {$media->id}");
        } catch (\Throwable $e) {
            $this->warn("  errore cover $cover: " . $e->getMessage());
        }
    }

    private function fetchUrl(string $url): ?string
    {
        $ctx  = stream_context_create(['http' => [
            'timeout'         => 15,
            'follow_location' => true,
            'user_agent'      => 'ganaghello-migration/1.0',
        ]]);
        $data = @file_get_contents($url, false, $ctx);
        if ($data === false || strlen($data) < 100) {
            $this->warn("  download fallito: " . Str::limit($url, 70));
            return null;
        }
        return $data;
    }
}
