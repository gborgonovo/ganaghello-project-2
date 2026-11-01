<?php

namespace App\Actions;

use App\Models\Expense;
use App\Models\RegistroScrittura;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/** Le spese itemizzate di un task, inserite a mano (la ricevuta resta fuori). */
class SpesaActions
{
    public const CATEGORIE = ['materiali', 'manodopera', 'professionisti', 'oneri', 'altro'];
    public const CAMPI     = ['description', 'amount', 'expense_date', 'category', 'supplier', 'notes'];

    public function crea(?User $utente, Task $task, array $dati): Expense
    {
        $dati = $this->valida($this->normalizza($dati), true);
        $e = $task->expenses()->create($dati);
        RegistroScrittura::annota($utente, 'crea', 'spesa', $e->id,
            "Spesa «{$e->description}» di " . number_format($e->amount, 2, ',', '.') . " € su «{$task->title}»");

        return $e;
    }

    /** Modifica parziale: solo i CAMPI presenti in $dati. */
    public function modifica(?User $utente, Expense $e, array $dati): Expense
    {
        $dati = $this->valida($this->normalizza(array_intersect_key($dati, array_flip(self::CAMPI))), false);
        $originali = $e->getOriginal();
        $e->update($dati);
        if ($diff = RegistroScrittura::differenze($e, $originali)) {
            RegistroScrittura::annota($utente, 'modifica', 'spesa', $e->id, "Spesa «{$e->description}» modificata: " . implode(', ', array_keys($diff)), $diff);
        }

        return $e->fresh();
    }

    public function elimina(?User $utente, Expense $e): void
    {
        $e->delete();
        RegistroScrittura::annota($utente, 'elimina', 'spesa', $e->id, "Spesa «{$e->description}» eliminata (recuperabile)");
    }

    public function ripristina(?User $utente, Expense $e): Expense
    {
        if ($e->trashed()) {
            $e->restore();
            RegistroScrittura::annota($utente, 'ripristina', 'spesa', $e->id, "Spesa «{$e->description}» ripristinata");
        }

        return $e->fresh();
    }

    private function valida(array $dati, bool $creazione): array
    {
        $obbligo = $creazione ? 'required' : 'sometimes';

        return Validator::make($dati, [
            'description'  => "{$obbligo}|string|max:255",
            'amount'       => "{$obbligo}|numeric|min:0.01",
            'expense_date' => 'nullable|date',
            'category'     => ['nullable', Rule::in(self::CATEGORIE)],
            'supplier'     => 'nullable|string|max:255',
            'notes'        => 'nullable|string',
        ], [], ['description' => 'descrizione', 'amount' => 'importo', 'expense_date' => 'data', 'category' => 'categoria'])->validate();
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
