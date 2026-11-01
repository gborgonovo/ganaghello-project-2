<?php

namespace App\Actions;

use App\Exceptions\RegolaDominio;
use App\Models\Inspiration;
use App\Models\RegistroScrittura;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/** Le ispirazioni della moodboard. Le immagini (caricate o da indirizzo) le gestisce l'interfaccia. */
class IspirazioneActions
{
    public const CAMPI = ['title', 'description', 'url', 'cover', 'area_id'];

    public function crea(?User $utente, array $dati, bool $conImmagine = false): Inspiration
    {
        $dati = $this->valida(Normalizza::testi(array_intersect_key($dati, array_flip(self::CAMPI))));
        if (!$conImmagine && empty($dati['title']) && empty($dati['description']) && empty($dati['url']) && empty($dati['cover'])) {
            throw new RegolaDominio('Aggiungi almeno un contenuto: titolo, descrizione o link.');
        }

        $insp = Inspiration::create($dati + ['user_id' => $utente?->id]);
        RegistroScrittura::annota($utente, 'crea', 'ispirazione', $insp->id, 'Ispirazione «' . $this->etichetta($insp) . '» creata');

        return $insp->fresh();
    }

    public function modifica(?User $utente, Inspiration $insp, array $dati): Inspiration
    {
        $dati = $this->valida(Normalizza::testi(array_intersect_key($dati, array_flip(self::CAMPI))));
        $originali = $insp->getOriginal();
        $insp->update($dati);
        if ($diff = RegistroScrittura::differenze($insp, $originali)) {
            RegistroScrittura::annota($utente, 'modifica', 'ispirazione', $insp->id, 'Ispirazione «' . $this->etichetta($insp) . '» modificata', $diff);
        }

        return $insp->fresh();
    }

    /** Eliminazione recuperabile della sola ispirazione: le immagini restano (le toglie l'interfaccia, se vuole). */
    public function elimina(?User $utente, Inspiration $insp): void
    {
        $insp->delete();
        RegistroScrittura::annota($utente, 'elimina', 'ispirazione', $insp->id, 'Ispirazione «' . $this->etichetta($insp) . '» eliminata (recuperabile)');
    }

    public function ripristina(?User $utente, Inspiration $insp): Inspiration
    {
        if ($insp->trashed()) {
            $insp->restore();
            RegistroScrittura::annota($utente, 'ripristina', 'ispirazione', $insp->id, 'Ispirazione «' . $this->etichetta($insp) . '» ripristinata');
        }

        return $insp->fresh();
    }

    private function valida(array $dati): array
    {
        return Validator::make($dati, [
            'title'       => 'nullable|string|max:200',
            'description' => 'nullable|string',
            'url'         => 'nullable|url|max:500',
            'cover'       => 'nullable|url|max:1000',
            'area_id'     => 'nullable|integer|exists:areas,id',
        ], [], ['title' => 'titolo', 'url' => 'link', 'cover' => 'immagine'])->validate();
    }

    private function etichetta(Inspiration $insp): string
    {
        return $insp->title ?: Str::limit((string) ($insp->description ?: $insp->url ?: "#{$insp->id}"), 50);
    }
}
