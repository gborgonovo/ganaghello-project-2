<?php

namespace App\Actions;

use App\Models\Attachment;
use App\Models\Entry;
use App\Models\Post;
use App\Models\RegistroScrittura;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * I post del blog. L'action non pubblica né blocca nulla di suo: il divieto di rendere pubblico
 * o di toccare un post pubblico vale per l'MCP ed è nei suoi tool; dall'app la pubblicazione resta
 * un gesto dell'utente.
 */
class PostActions
{
    public const CAMPI       = ['title', 'slug', 'excerpt', 'content', 'visibility', 'area_id', 'published_at'];
    public const VISIBILITA  = ['private', 'draft', 'public'];
    private const SENZA_TITOLO = 'Senza titolo';

    /**
     * Un post in bozza dalle voci scelte: testi in ordine cronologico separati da una riga vuota
     * (salvo $testo), area se comune a tutte le voci, data la più recente, le stesse foto (media
     * condivisi) e il collegamento entry_post.
     */
    public function componi(?User $utente, array $voceIds, ?string $titolo = null, ?string $testo = null): Post
    {
        $voci = Entry::whereIn('id', $voceIds)->with('attachments')
            ->orderBy('entry_date')->orderByRaw('CASE WHEN entry_time IS NULL THEN 1 ELSE 0 END')->orderBy('entry_time')
            ->get();
        if ($voci->isEmpty()) {
            throw new \App\Exceptions\RegolaDominio('Nessuna delle voci indicate esiste.');
        }

        $aree   = $voci->pluck('area_id')->unique();
        $titolo = trim((string) $titolo) ?: ($voci->count() === 1 ? $voci->first()->title : null) ?: self::SENZA_TITOLO;
        $testo  = trim((string) $testo) ?: $voci->pluck('content')->map(fn ($c) => trim((string) $c))->filter()->implode("\n\n");

        return DB::transaction(function () use ($utente, $voci, $aree, $titolo, $testo) {
            $post = Post::create([
                'user_id'      => $utente?->id,
                'area_id'      => ($aree->count() === 1 && $aree->first()) ? $aree->first() : null,
                'title'        => $titolo,
                'slug'         => $this->slugProvvisorio(),
                'content'      => $testo,
                'visibility'   => 'draft',
                'published_at' => $voci->max('entry_date'),
            ]);
            $post->entries()->attach($voci->pluck('id')->all());

            $seq = 0;
            foreach ($voci as $voce) {
                foreach ($voce->attachments as $att) {
                    Attachment::create(['attachable_type' => Post::class, 'attachable_id' => $post->id, 'media_id' => $att->media_id, 'sequence' => $seq++]);
                }
            }

            RegistroScrittura::annota($utente, 'componi', 'post', $post->id, "Post «{$post->title}» composto da {$voci->count()} voci (bozza)");

            return $post;
        });
    }

    /** Una bozza vuota (l'editor del blog la crea all'apertura di /blog/nuovo). */
    public function crea(?User $utente, array $dati = []): Post
    {
        $post = Post::create([
            'user_id'    => $utente?->id,
            'title'      => trim($dati['title'] ?? '') ?: self::SENZA_TITOLO,
            'slug'       => $this->slugProvvisorio(),
            'content'    => $dati['content'] ?? '',
            'visibility' => 'draft',
        ]);
        RegistroScrittura::annota($utente, 'crea', 'post', $post->id, "Post «{$post->title}» creato (bozza)");

        return $post;
    }

    /**
     * Modifica parziale. Lo slug si rende unico; finché è quello provvisorio segue il titolo.
     * Pubblicare senza data di pubblicazione la imposta a ora.
     */
    public function modifica(?User $utente, Post $post, array $dati): Post
    {
        $dati = array_intersect_key($dati, array_flip(self::CAMPI));
        foreach (['title', 'excerpt', 'slug'] as $k) {
            if (array_key_exists($k, $dati) && is_string($dati[$k])) {
                $dati[$k] = trim($dati[$k]) === '' ? null : trim($dati[$k]);
            }
        }
        Validator::make($dati, [
            'title'        => 'sometimes|required|string|max:255',
            'slug'         => 'nullable|string|max:255',
            'excerpt'      => 'nullable|string|max:500',
            'content'      => 'nullable|string',
            'visibility'   => ['sometimes', Rule::in(self::VISIBILITA)],
            'area_id'      => 'nullable|integer|exists:areas,id',
            'published_at' => 'nullable|date',
        ], [], ['title' => 'titolo', 'excerpt' => 'sommario'])->validate();

        if (array_key_exists('content', $dati)) {
            $dati['content'] = trim((string) $dati['content']);
        }

        $titolo = $dati['title'] ?? $post->title;
        if (array_key_exists('slug', $dati) || str_starts_with((string) $post->slug, 'bozza-') || !$post->slug) {
            $dati['slug'] = $this->slugUnico($dati['slug'] ?? (str_starts_with((string) $post->slug, 'bozza-') ? $titolo : $post->slug), $post->id);
        }

        if (($dati['visibility'] ?? $post->visibility) === 'public' && !$post->published_at && empty($dati['published_at'])) {
            $dati['published_at'] = now();
        }
        if (!empty($dati['published_at']) && is_string($dati['published_at'])) {
            $dati['published_at'] = Carbon::parse($dati['published_at']);
        }

        $originali = $post->getOriginal();
        $post->update($dati);
        if ($diff = RegistroScrittura::differenze($post, $originali)) {
            RegistroScrittura::annota($utente, 'modifica', 'post', $post->id, "Post «{$post->title}» modificato: " . implode(', ', array_keys($diff)), $diff);
        }

        return $post->fresh();
    }

    /** Eliminazione recuperabile del solo post: le foto restano (le toglie l'interfaccia, se vuole). */
    public function elimina(?User $utente, Post $post): void
    {
        $post->delete();
        RegistroScrittura::annota($utente, 'elimina', 'post', $post->id, "Post «{$post->title}» eliminato (recuperabile)");
    }

    public function ripristina(?User $utente, Post $post): Post
    {
        if ($post->trashed()) {
            $post->restore();
            RegistroScrittura::annota($utente, 'ripristina', 'post', $post->id, "Post «{$post->title}» ripristinato");
        }

        return $post->fresh();
    }

    public function slugUnico(string $da, ?int $escludi = null): string
    {
        $base = Str::slug($da) ?: 'post';
        $slug = $base;
        $i    = 2;
        while (Post::withTrashed()->where('slug', $slug)->when($escludi, fn ($q) => $q->where('id', '!=', $escludi))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    private function slugProvvisorio(): string
    {
        return 'bozza-' . Str::random(8);
    }
}
