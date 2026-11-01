<?php

namespace App\Actions;

use App\Exceptions\RegolaDominio;
use App\Models\RegistroScrittura;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

/**
 * I tag e i tag sugli elementi. Il nome è unico anche fra gli eliminati (vincolo del database):
 * usare il nome di un tag eliminato lo ripristina, con i suoi collegamenti.
 */
class TagActions
{
    /** Il tag con questo nome: esistente, ripristinato se eliminato, o nuovo. */
    public function trovaOCrea(?User $utente, string $nome, ?string $colore = null): Tag
    {
        $nome = $this->nome($nome);
        $tag  = Tag::withTrashed()->where('name', $nome)->first();

        if ($tag?->trashed()) {
            $tag->restore();
            RegistroScrittura::annota($utente, 'ripristina', 'tag', $tag->id, "Tag «{$tag->name}» ripristinato");
        } elseif (!$tag) {
            $tag = Tag::create(['name' => $nome, 'color' => $colore]);
            RegistroScrittura::annota($utente, 'crea', 'tag', $tag->id, "Tag «{$tag->name}» creato");
        }

        return $tag;
    }

    /** Creazione esplicita (impostazioni, MCP): un tag attivo con lo stesso nome è un errore. */
    public function crea(?User $utente, array $dati): Tag
    {
        $nome = $this->nome($dati['name'] ?? '');
        if (Tag::where('name', $nome)->exists()) {
            throw new RegolaDominio("Il tag «{$nome}» esiste già.");
        }
        $tag = $this->trovaOCrea($utente, $nome, $dati['color'] ?? null);
        if (array_key_exists('color', $dati) && $tag->color !== $dati['color']) {
            $tag->update(['color' => $dati['color']]);
        }

        return $tag->fresh();
    }

    /** Rinomina o ricolora. Un nome già usato, anche da un tag eliminato, è un errore: niente fusioni silenziose. */
    public function modifica(?User $utente, Tag $tag, array $dati): Tag
    {
        $dati = array_intersect_key($dati, array_flip(['name', 'color']));
        if (array_key_exists('name', $dati)) {
            $dati['name'] = $this->nome($dati['name']);
            $altro = Tag::withTrashed()->where('name', $dati['name'])->where('id', '!=', $tag->id)->first();
            if ($altro) {
                throw new RegolaDominio("Il nome «{$dati['name']}» è già di un altro tag" . ($altro->trashed() ? ' (eliminato: ripristinalo invece)' : '') . '.');
            }
        }
        Validator::make($dati, ['color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/']], [], ['color' => 'colore'])->validate();

        $originali = $tag->getOriginal();
        $tag->update($dati);
        if ($diff = RegistroScrittura::differenze($tag, $originali)) {
            RegistroScrittura::annota($utente, 'modifica', 'tag', $tag->id, "Tag «{$tag->name}» modificato", $diff);
        }

        return $tag->fresh();
    }

    /** Eliminazione recuperabile: il tag sparisce da elenchi ed elementi, i collegamenti restano. */
    public function elimina(?User $utente, Tag $tag): void
    {
        $tag->delete();
        RegistroScrittura::annota($utente, 'elimina', 'tag', $tag->id, "Tag «{$tag->name}» eliminato (recuperabile)");
    }

    public function ripristina(?User $utente, Tag $tag): Tag
    {
        if ($tag->trashed()) {
            $tag->restore();
            RegistroScrittura::annota($utente, 'ripristina', 'tag', $tag->id, "Tag «{$tag->name}» ripristinato");
        }

        return $tag->fresh();
    }

    /** Nomi di tag (creati o ripristinati se serve) da aggiungere, nomi o id da togliere. Restituisce i nomi risultanti. */
    public function applica(?User $utente, Model $elemento, array $aggiungi = [], array $togli = []): array
    {
        $prima = $elemento->tags()->pluck('name')->all();

        $nuovi = collect($aggiungi)
            ->map(fn ($n) => mb_strtolower(trim((string) $n)))
            ->filter()
            ->unique()
            ->map(fn ($n) => $this->trovaOCrea($utente, $n)->id);

        $daTogliere = collect($togli)->map(fn ($t) => is_int($t) ? $t : Tag::where('name', mb_strtolower(trim((string) $t)))->value('id'))->filter();

        if ($nuovi->isNotEmpty()) {
            $elemento->tags()->syncWithoutDetaching($nuovi->all());
        }
        if ($daTogliere->isNotEmpty()) {
            $elemento->tags()->detach($daTogliere->all());
        }

        $dopo = $elemento->tags()->pluck('name')->all();
        if ($prima !== $dopo) {
            RegistroScrittura::annota($utente, 'tag', Entita::di($elemento), $elemento->getKey(),
                'Tag di ' . Entita::etichetta($elemento) . ': ' . (implode(', ', $dopo) ?: 'nessuno'),
                ['tag' => [$prima, $dopo]]);
        }

        return $dopo;
    }

    private function nome(string $nome): string
    {
        $nome = mb_strtolower(trim($nome));
        Validator::make(['name' => $nome], ['name' => 'required|string|max:100'], [], ['name' => 'nome'])->validate();

        return $nome;
    }
}
