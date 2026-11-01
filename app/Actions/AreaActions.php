<?php

namespace App\Actions;

use App\Exceptions\RegolaDominio;
use App\Models\Area;
use App\Models\RegistroScrittura;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

/** I luoghi della casa, ad albero. L'observer sincronizza nome, descrizione e padre con Mnemosyne. */
class AreaActions
{
    public const CAMPI = ['name', 'description', 'color', 'text_color', 'status', 'parent_area_id', 'sequence'];

    /** Senza `sequence`, l'area va in coda. */
    public function crea(?User $utente, array $dati): Area
    {
        $dati = $this->valida(Normalizza::testi(array_intersect_key($dati, array_flip(self::CAMPI))), true);
        $this->controllaPadre($dati['parent_area_id'] ?? null, null);

        $area = Area::create($dati + ['sequence' => (Area::max('sequence') ?? 0) + 1]);
        RegistroScrittura::annota($utente, 'crea', 'area', $area->id, "Area «{$area->name}» creata");

        return $area->fresh();
    }

    public function modifica(?User $utente, Area $area, array $dati): Area
    {
        $dati = $this->valida(Normalizza::testi(array_intersect_key($dati, array_flip(self::CAMPI))), false);
        if (array_key_exists('parent_area_id', $dati)) {
            $this->controllaPadre($dati['parent_area_id'], $area);
        }

        $originali = $area->getOriginal();
        $area->update($dati);
        if ($diff = RegistroScrittura::differenze($area, $originali)) {
            RegistroScrittura::annota($utente, 'modifica', 'area', $area->id, "Area «{$area->name}» modificata: " . implode(', ', array_keys($diff)), $diff);
        }

        return $area->fresh();
    }

    /** Eliminazione recuperabile, solo per un'area senza sotto-aree e senza elementi collegati. */
    public function elimina(?User $utente, Area $area): void
    {
        $area->loadCount(['children', 'tasks', 'notes', 'entries', 'inspirations', 'posts']);
        if ($area->children_count > 0) {
            throw new RegolaDominio('Ha sotto-aree: rimuovile prima.');
        }
        $inUso = $area->tasks_count + $area->notes_count + $area->entries_count + $area->inspirations_count + $area->posts_count;
        if ($inUso > 0) {
            throw new RegolaDominio("Usata da {$inUso} elementi: riassegna prima.");
        }

        $area->delete();
        RegistroScrittura::annota($utente, 'elimina', 'area', $area->id, "Area «{$area->name}» eliminata (recuperabile)");
    }

    public function ripristina(?User $utente, Area $area): Area
    {
        if ($area->trashed()) {
            if ($area->parent_area_id && !Area::find($area->parent_area_id)) {
                throw new RegolaDominio("L'area padre di «{$area->name}» è eliminata: ripristina prima quella.");
            }
            $area->restore();
            RegistroScrittura::annota($utente, 'ripristina', 'area', $area->id, "Area «{$area->name}» ripristinata");
        }

        return $area->fresh();
    }

    private function valida(array $dati, bool $creazione): array
    {
        $colore = ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'];

        return Validator::make($dati, [
            'name'           => ($creazione ? 'required' : 'sometimes|required') . '|string|max:255',
            'description'    => 'nullable|string',
            'color'          => $colore,
            'text_color'     => $colore,
            'status'         => 'nullable|string|max:30',
            'parent_area_id' => 'nullable|integer',
            'sequence'       => 'sometimes|integer',
        ], [], ['name' => 'nome', 'color' => 'colore', 'text_color' => 'colore del testo'])->validate();
    }

    private function controllaPadre(?int $padreId, ?Area $area): void
    {
        if (!$padreId) {
            return;
        }
        $padre = Area::find($padreId) ?? throw new RegolaDominio("L'area padre {$padreId} non esiste o è eliminata.");
        for ($a = $padre; $area && $a; $a = $a->parent_area_id ? Area::find($a->parent_area_id) : null) {
            if ($a->id === $area->id) {
                throw new RegolaDominio("L'area «{$padre->name}» non può contenere «{$area->name}»: si creerebbe un ciclo.");
            }
        }
    }
}
