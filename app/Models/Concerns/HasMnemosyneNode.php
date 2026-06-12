<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Da' a un modello un nome di nodo Mnemosyne stabile e riusabile.
 *
 * Il nome viene generato una volta sola (slug dell'etichetta + id) e persistito in
 * `mnemosyne_node_name`, poi riusato per sempre: e' il legame tra la riga SQL e il nodo,
 * e l'upsert di Mnemosyne si aggancia ad esso. Mnemosyne normalizza il nome in modo
 * coerente (i trattini diventano underscore), quindi inviare/leggere con la forma salvata
 * funziona, e i target nelle `relations` combaciano.
 */
trait HasMnemosyneNode
{
    /** Etichetta umana da cui derivare il nome (titolo, nome area, ...). */
    abstract public function mnemosyneLabel(): string;

    public function mnemosyneName(): string
    {
        if (!empty($this->mnemosyne_node_name)) {
            return $this->mnemosyne_node_name;
        }

        $base = Str::slug(Str::limit($this->mnemosyneLabel(), 60, '')) ?: Str::lower(class_basename($this));
        $name = $base . '-' . $this->getKey();

        // saveQuietly: non vogliamo che l'assegnazione del nome riscateni gli observer.
        $this->forceFill(['mnemosyne_node_name' => $name])->saveQuietly();

        return $name;
    }
}
