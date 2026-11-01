<?php

namespace App\Actions;

use App\Models\Note;
use App\Models\RegistroScrittura;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/** Gli appunti al volo. Non sono sincronizzati con Mnemosyne. */
class NotaActions
{
    public const CAMPI = ['title', 'content', 'area_id'];

    public function crea(?User $utente, array $dati): Note
    {
        $dati = $this->valida(Normalizza::testi(array_intersect_key($dati, array_flip(self::CAMPI))), true);
        $nota = Note::create($dati + ['user_id' => $utente?->id]);
        RegistroScrittura::annota($utente, 'crea', 'nota', $nota->id, 'Nota «' . ($nota->title ?: Str::limit($nota->content, 50)) . '» creata');

        return $nota->fresh();
    }

    public function modifica(?User $utente, Note $nota, array $dati): Note
    {
        $dati = $this->valida(Normalizza::testi(array_intersect_key($dati, array_flip(self::CAMPI))), false);
        $originali = $nota->getOriginal();
        $nota->update($dati);
        if ($diff = RegistroScrittura::differenze($nota, $originali)) {
            RegistroScrittura::annota($utente, 'modifica', 'nota', $nota->id, 'Nota «' . ($nota->title ?: Str::limit($nota->content, 50)) . '» modificata', $diff);
        }

        return $nota->fresh();
    }

    public function elimina(?User $utente, Note $nota): void
    {
        $nota->delete();
        RegistroScrittura::annota($utente, 'elimina', 'nota', $nota->id, 'Nota «' . ($nota->title ?: Str::limit($nota->content, 50)) . '» eliminata (recuperabile)');
    }

    public function ripristina(?User $utente, Note $nota): Note
    {
        if ($nota->trashed()) {
            $nota->restore();
            RegistroScrittura::annota($utente, 'ripristina', 'nota', $nota->id, 'Nota «' . ($nota->title ?: Str::limit($nota->content, 50)) . '» ripristinata');
        }

        return $nota->fresh();
    }

    private function valida(array $dati, bool $creazione): array
    {
        return Validator::make($dati, [
            'content' => ($creazione ? 'required' : 'sometimes|required') . '|string',
            'title'   => 'nullable|string|max:255',
            'area_id' => 'nullable|integer|exists:areas,id',
        ], [], ['content' => 'testo', 'title' => 'titolo'])->validate();
    }
}
