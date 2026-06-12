<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Post;
use App\Services\MediaService;
use Illuminate\Support\Facades\Storage;

class PublicBlogController extends Controller
{
    /** Indice della vetrina: post pubblici in ordine cronologico inverso. */
    public function index()
    {
        $posts = Post::where('visibility', 'public')
            ->with('attachments.media')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('blog.index', compact('posts'));
    }

    /** Pagina del singolo post pubblico (per slug). */
    public function show(string $slug)
    {
        $post = Post::where('slug', $slug)
            ->where('visibility', 'public')
            ->with(['attachments.media', 'area'])
            ->firstOrFail();

        return view('blog.show', compact('post'));
    }

    /**
     * Serving immagini filtrato per visibilita': serve il media SOLO se appartiene
     * (via attachments) a un post pubblico. Altrimenti 404.
     */
    public function image(Media $media, string $size = 'medium')
    {
        $belongsToPublicPost = $media->attachments()
            ->where('attachable_type', Post::class)
            ->whereIn('attachable_id', Post::where('visibility', 'public')->select('id'))
            ->exists();

        abort_unless($belongsToPublicPost, 404);

        $service   = app(MediaService::class);
        $validSize = in_array($size, ['thumb', 'medium', 'original']) ? $size : 'medium';

        $path = $media->isImage() ? $service->path($media, $validSize) : $media->path;

        if (!Storage::disk('private')->exists($path)) {
            abort(404);
        }

        $fullPath = Storage::disk('private')->path($path);

        return response()->file($fullPath, [
            'Content-Type'  => $media->isImage() ? 'image/jpeg' : $media->mime_type,
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
