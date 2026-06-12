<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function serve(Request $request, Media $media, string $size = 'original')
    {
        $service   = app(MediaService::class);
        $validSize = in_array($size, ['thumb', 'medium', 'original', 'file']) ? $size : 'original';

        if (!$media->isImage()) {
            $path = $media->path;
        } else {
            $path = $service->path($media, $validSize);
        }

        if (!Storage::disk('private')->exists($path)) {
            abort(404);
        }

        $mime        = $media->isImage() ? 'image/jpeg' : $media->mime_type;
        $fullPath    = Storage::disk('private')->path($path);
        $disposition = $media->isImage() ? 'inline' : 'attachment';

        return response()->file($fullPath, [
            'Content-Type'        => $mime,
            'Cache-Control'       => 'private, max-age=86400',
            'Content-Disposition' => $disposition . '; filename="' . $media->original_filename . '"',
        ]);
    }
}
