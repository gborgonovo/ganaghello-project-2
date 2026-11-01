<?php

namespace App\Actions;

use App\Jobs\SyncEntryToMnemosyne;
use App\Models\Entry;
use App\Models\RegistroScrittura;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Le voci di diario, da qualunque porta. Le foto restano fuori: le gestisce l'editor
 * dell'app; qui si sa solo se la voce ne ha (o ne avrà) una, per sceglierne il tipo.
 */
class VoceActions
{
    public const CAMPI = ['title', 'content', 'entry_date', 'entry_time', 'area_id', 'kind'];

    /**
     * Dati: CAMPI più `task_ids`. `kind` assente o 'auto': lo decide Entry::defaultKind
     * (con $conFoto, polaroid). Senza `entry_date`, oggi; senza `entry_time`, l'ora attuale
     * solo se la voce è di oggi, altrimenti vuota (un'ora inventata sarebbe falsa).
     */
    public function crea(?User $utente, array $dati, bool $conFoto = false): Entry
    {
        $dati = $this->normalizza($dati);
        $oggi = Carbon::today('Europe/Rome')->toDateString();

        $dati['entry_date'] ??= $oggi;
        if (!array_key_exists('entry_time', $dati) && $dati['entry_date'] === $oggi) {
            $dati['entry_time'] = now('Europe/Rome')->format('H:i');
        }
        $this->valida($dati, true);

        $kind = $dati['kind'] ?? 'auto';
        $dati['kind'] = $kind === 'auto' ? Entry::defaultKind($dati['content'], $conFoto) : $kind;

        return DB::transaction(function () use ($utente, $dati) {
            $voce = Entry::create(array_intersect_key($dati, array_flip(self::CAMPI)) + ['user_id' => $utente?->id]);

            if (!empty($dati['task_ids'])) {
                $voce->tasks()->sync($dati['task_ids']);
                $this->risincronizza($voce);
            }

            RegistroScrittura::annota($utente, 'crea', 'voce', $voce->id,
                'Voce di diario «' . ($voce->title ?: Str::limit($voce->content, 50)) . "» del {$voce->entry_date->format('d/m/Y')}");

            return $voce->fresh();
        });
    }

    /**
     * Modifica parziale: solo le chiavi presenti. `task_ids`, se presente, sostituisce i task
     * collegati. `kind` 'auto' ricalcola il tipo con $conFoto.
     */
    public function modifica(?User $utente, Entry $voce, array $dati, bool $conFoto = false): Entry
    {
        $dati = $this->normalizza(array_intersect_key($dati, array_flip([...self::CAMPI, 'task_ids'])));
        $this->valida($dati, false);
        if (($dati['kind'] ?? null) === 'auto') {
            $dati['kind'] = Entry::defaultKind($dati['content'] ?? $voce->content, $conFoto);
        }

        return DB::transaction(function () use ($utente, $voce, $dati) {
            $originali = $voce->getOriginal();
            $voce->update(array_intersect_key($dati, array_flip(self::CAMPI)));
            $diff = RegistroScrittura::differenze($voce, $originali);

            if (array_key_exists('task_ids', $dati)) {
                $prima = $voce->tasks()->pluck('tasks.id')->sort()->values()->all();
                $voce->tasks()->sync($dati['task_ids'] ?? []);
                $dopo = $voce->tasks()->pluck('tasks.id')->sort()->values()->all();
                if ($prima != $dopo) {
                    $diff['task'] = [$prima, $dopo];
                    // I task sono relazioni del nodo della voce, che l'observer non vede.
                    $this->risincronizza($voce);
                }
            }

            if ($diff) {
                RegistroScrittura::annota($utente, 'modifica', 'voce', $voce->id,
                    'Voce «' . ($voce->title ?: Str::limit($voce->content, 50)) . '» modificata: ' . implode(', ', array_keys($diff)), $diff);
            }

            return $voce->fresh();
        });
    }

    /** Eliminazione recuperabile della sola voce: allegati e file restano, così il ripristino è completo. */
    public function elimina(?User $utente, Entry $voce): void
    {
        $voce->delete();
        RegistroScrittura::annota($utente, 'elimina', 'voce', $voce->id, 'Voce «' . ($voce->title ?: Str::limit($voce->content, 50)) . '» eliminata (recuperabile)');
    }

    public function ripristina(?User $utente, Entry $voce): Entry
    {
        if ($voce->trashed()) {
            $voce->restore();
            RegistroScrittura::annota($utente, 'ripristina', 'voce', $voce->id, 'Voce «' . ($voce->title ?: Str::limit($voce->content, 50)) . '» ripristinata');
        }

        return $voce->fresh();
    }

    /** Il nodo della voce in Mnemosyne porta area e task che documenta. */
    public function risincronizza(Entry $voce): void
    {
        if (config('services.mnemosyne.sync', true)) {
            SyncEntryToMnemosyne::dispatch($voce->id)->afterCommit();
        }
    }

    private function valida(array $dati, bool $creazione): void
    {
        Validator::make($dati, [
            'content'    => ($creazione ? 'required' : 'sometimes|required') . '|string',
            'title'      => 'nullable|string|max:255',
            'entry_date' => ($creazione ? 'required' : 'sometimes|required') . '|date',
            'entry_time' => ['nullable', 'regex:/^\d{1,2}:\d{2}$/'],
            'area_id'    => 'nullable|integer|exists:areas,id',
            'kind'       => ['nullable', Rule::in(['auto', ...Entry::KINDS])],
            'task_ids'   => 'nullable|array',
            'task_ids.*' => 'integer|exists:tasks,id',
        ], [], ['content' => 'testo', 'title' => 'titolo', 'entry_date' => 'data', 'entry_time' => 'ora'])->validate();
    }

    private function normalizza(array $dati): array
    {
        foreach ($dati as $k => $v) {
            if (is_string($v)) {
                $v = trim($v);
                $dati[$k] = $v === '' ? null : $v;
            }
        }

        return $dati;
    }
}
