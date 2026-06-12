<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class MediaService
{
    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/heic'];

    private const SIZES = [
        'thumb'    => 400,
        'medium'   => 1400,
        'original' => 1920,
    ];

    public function store(\Illuminate\Http\UploadedFile $file, ?string $context = null): Media
    {
        return $this->persist(
            $file->getRealPath(),
            $file->getClientOriginalName(),
            $file->getMimeType(),
            $file->getSize(),
            $file->getClientOriginalExtension(),
            Auth::id(),
            $context,
        );
    }

    /**
     * Salva un file gia' presente sul filesystem (es. scaricato da remoto in un
     * temporaneo). Pensato per l'uso da CLI, dove non c'e' Auth: lo user_id e' esplicito.
     */
    public function storeFromPath(string $absolutePath, string $originalName, int $userId, ?string $context = null): Media
    {
        $mime = \Illuminate\Support\Facades\File::mimeType($absolutePath) ?: 'application/octet-stream';
        $size = filesize($absolutePath) ?: 0;
        $ext  = pathinfo($originalName, PATHINFO_EXTENSION) ?: 'jpg';

        return $this->persist($absolutePath, $originalName, $mime, $size, $ext, $userId, $context);
    }

    private function persist(string $realPath, string $originalName, string $mime, int $size, string $ext, ?int $userId, ?string $context = null): Media
    {
        $isImage  = in_array($mime, self::IMAGE_MIMES);
        $uuid     = Str::uuid()->toString();
        $folder   = now()->format('Y/m');
        $basePath = "media/{$folder}/{$uuid}";

        if ($isImage) {
            $this->storeImageVersions($realPath, $basePath);
            $storedPath = "{$basePath}/original";
        } else {
            // PDF o altro documento: salvato as-is, nessun processing
            $storedPath = "{$basePath}/file.{$ext}";
            Storage::disk('private')->put($storedPath, file_get_contents($realPath));
        }

        return Media::create([
            'user_id'           => $userId,
            'filename'          => $uuid,
            'original_filename' => $originalName,
            'path'              => $storedPath,
            'folder'            => $folder,
            'mime_type'         => $mime,
            'size'              => $size,
            'context'           => $context,
        ]);
    }

    public function delete(Media $media): void
    {
        // Rimuove tutti i file fisici (immagine: 3 versioni; doc: 1 file)
        $prefix = "media/{$media->folder}/{$media->filename}";
        foreach (Storage::disk('private')->allFiles($prefix) as $f) {
            Storage::disk('private')->delete($f);
        }
        // Rimuove la directory se vuota
        Storage::disk('private')->deleteDirectory($prefix);

        $media->delete();
    }

    public function path(Media $media, string $size = 'original'): string
    {
        if (!$media->isImage()) {
            return $media->path;
        }

        return "media/{$media->folder}/{$media->filename}/{$size}";
    }

    private function storeImageVersions(string $realPath, string $basePath): void
    {
        $manager = new ImageManager(new Driver());
        $image   = $manager->read($realPath);

        // HEIC: Intervention converte automaticamente leggendo il file
        foreach (self::SIZES as $sizeName => $maxPx) {
            $img = clone $image;
            if ($img->width() > $maxPx || $img->height() > $maxPx) {
                $img->scaleDown($maxPx, $maxPx);
            }

            $quality  = match($sizeName) { 'thumb' => 80, 'medium' => 85, default => 90 };
            $encoded  = $img->toJpeg($quality);
            $filePath = "{$basePath}/{$sizeName}";

            Storage::disk('private')->put($filePath, (string) $encoded);
        }
    }
}
