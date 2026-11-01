<?php

namespace App\Actions;

use App\Models\RegistroScrittura;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Tag sugli elementi del dominio. La gestione dei tag in sé (crea, rinomina, elimina) arriva con il passo dei tag. */
class TagActions
{
    /** Nomi di tag (creati se mancano) da aggiungere, nomi o id da togliere. Restituisce i nomi risultanti. */
    public function applica(?User $utente, Model $elemento, array $aggiungi = [], array $togli = []): array
    {
        $prima = $elemento->tags()->pluck('name')->all();

        $nuovi = collect($aggiungi)
            ->map(fn ($n) => mb_strtolower(trim((string) $n)))
            ->filter()
            ->unique()
            ->map(fn ($n) => Tag::firstOrCreate(['name' => $n])->id);

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
}
