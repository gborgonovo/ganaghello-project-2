<?php

namespace App\Actions;

use App\Models\RegistroScrittura;
use App\Models\Task;
use App\Models\TaskUpdate;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/** Gli aggiornamenti datati di un task: il suo mini-diario operativo. */
class AggiornamentoActions
{
    public function crea(?User $utente, Task $task, ?string $testo): TaskUpdate
    {
        $testo = $this->valida($testo);
        $u = TaskUpdate::create(['task_id' => $task->id, 'content' => $testo]);
        RegistroScrittura::annota($utente, 'crea', 'aggiornamento', $u->id, "Aggiornamento su «{$task->title}»: " . Str::limit($testo, 80));

        return $u;
    }

    public function modifica(?User $utente, TaskUpdate $u, ?string $testo): TaskUpdate
    {
        $testo = $this->valida($testo);
        $originali = $u->getOriginal();
        $u->update(['content' => $testo]);
        if ($diff = RegistroScrittura::differenze($u, $originali)) {
            RegistroScrittura::annota($utente, 'modifica', 'aggiornamento', $u->id, "Aggiornamento su «{$u->task?->title}» modificato", $diff);
        }

        return $u->fresh();
    }

    public function elimina(?User $utente, TaskUpdate $u): void
    {
        $u->delete();
        RegistroScrittura::annota($utente, 'elimina', 'aggiornamento', $u->id, "Aggiornamento su «{$u->task?->title}» eliminato (recuperabile)");
    }

    public function ripristina(?User $utente, TaskUpdate $u): TaskUpdate
    {
        if ($u->trashed()) {
            $u->restore();
            RegistroScrittura::annota($utente, 'ripristina', 'aggiornamento', $u->id, "Aggiornamento su «{$u->task?->title}» ripristinato");
        }

        return $u->fresh();
    }

    private function valida(?string $testo): string
    {
        $testo = trim((string) $testo);
        Validator::make(['content' => $testo], ['content' => 'required|string'], [], ['content' => 'testo'])->validate();

        return $testo;
    }
}
