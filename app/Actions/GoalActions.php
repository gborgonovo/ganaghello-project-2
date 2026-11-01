<?php

namespace App\Actions;

use App\Exceptions\RegolaDominio;
use App\Models\Goal;
use App\Models\RegistroScrittura;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/** Gli obiettivi strategici. L'observer sincronizza titolo, descrizione, scadenza, stato e padre. */
class GoalActions
{
    public const CAMPI = ['title', 'description', 'deadline', 'status', 'parent_goal_id'];
    public const STATI = ['active', 'raggiunto', 'abbandonato'];

    public function crea(?User $utente, array $dati): Goal
    {
        $dati = $this->valida(Normalizza::testi(array_intersect_key($dati, array_flip(self::CAMPI))), true);
        $this->controllaPadre($dati['parent_goal_id'] ?? null, null);

        $padre = $dati['parent_goal_id'] ?? null;
        $goal  = Goal::create($dati + [
            'user_id'  => $utente?->id,
            'status'   => $dati['status'] ?? 'active',
            'sequence' => (Goal::where('parent_goal_id', $padre)->max('sequence') ?? 0) + 1,
        ]);
        RegistroScrittura::annota($utente, 'crea', 'goal', $goal->id, "Goal «{$goal->title}» creato");

        return $goal->fresh();
    }

    public function modifica(?User $utente, Goal $goal, array $dati): Goal
    {
        $dati = $this->valida(Normalizza::testi(array_intersect_key($dati, array_flip(self::CAMPI))), false);
        if (array_key_exists('parent_goal_id', $dati)) {
            $this->controllaPadre($dati['parent_goal_id'], $goal);
        }

        $originali = $goal->getOriginal();
        $goal->update($dati);
        if ($diff = RegistroScrittura::differenze($goal, $originali)) {
            RegistroScrittura::annota($utente, 'modifica', 'goal', $goal->id, "Goal «{$goal->title}» modificato: " . implode(', ', array_keys($diff)), $diff);
        }

        return $goal->fresh();
    }

    public function elimina(?User $utente, Goal $goal): void
    {
        $goal->delete();
        RegistroScrittura::annota($utente, 'elimina', 'goal', $goal->id, "Goal «{$goal->title}» eliminato (recuperabile)");
    }

    public function ripristina(?User $utente, Goal $goal): Goal
    {
        if ($goal->trashed()) {
            $goal->restore();
            RegistroScrittura::annota($utente, 'ripristina', 'goal', $goal->id, "Goal «{$goal->title}» ripristinato");
        }

        return $goal->fresh();
    }

    private function valida(array $dati, bool $creazione): array
    {
        return Validator::make($dati, [
            'title'          => ($creazione ? 'required' : 'sometimes|required') . '|string|max:255',
            'description'    => 'nullable|string',
            'deadline'       => 'nullable|date',
            'status'         => ['sometimes', Rule::in(self::STATI)],
            'parent_goal_id' => 'nullable|integer',
        ], [], ['title' => 'titolo', 'deadline' => 'scadenza', 'status' => 'stato'])->validate();
    }

    private function controllaPadre(?int $padreId, ?Goal $goal): void
    {
        if (!$padreId) {
            return;
        }
        $padre = Goal::find($padreId) ?? throw new RegolaDominio("Il goal padre {$padreId} non esiste o è eliminato.");
        for ($g = $padre; $goal && $g; $g = $g->parent_goal_id ? Goal::find($g->parent_goal_id) : null) {
            if ($g->id === $goal->id) {
                throw new RegolaDominio("Il goal «{$padre->title}» non può essere padre di «{$goal->title}»: si creerebbe un ciclo.");
            }
        }
    }
}
