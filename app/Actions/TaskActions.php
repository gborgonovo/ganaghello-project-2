<?php

namespace App\Actions;

use App\Exceptions\RegolaDominio;
use App\Jobs\SyncTaskToMnemosyne;
use App\Models\RegistroScrittura;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Tutte le scritture sui task: interfaccia e server MCP passano da qui, così la regola
 * di stage e scadenza, la sync dei collegamenti con Mnemosyne e il registro valgono ovunque.
 */
class TaskActions
{
    public const ESECUZIONE = ['todo', 'doing', 'in_attesa'];
    public const CHIUSI     = ['done', 'archiviato'];

    public const CAMPI = ['title', 'description', 'area_id', 'parent_task_id', 'priority', 'executor', 'due_date',
                          'start_date', 'work_estimate', 'cost_min', 'cost_max', 'cost_basis', 'cost_real', 'completed_at'];

    public function __construct(private TagActions $tag)
    {
    }

    /**
     * Dati: i CAMPI del task più `stage` (codice) o `stage_id`, `assigned_to`, `collaboratori` (id),
     * `tag` (nomi), `goal_ids`. Con $scadenzaPredefinita una scadenza mancante diventa oggi invece di un errore.
     */
    public function crea(?User $utente, array $dati, bool $scadenzaPredefinita = false): Task
    {
        $dati  = $this->normalizza($dati);
        $stage = $this->stage($dati);
        $this->valida($dati, true);

        $attr = array_intersect_key($dati, array_flip(self::CAMPI));
        $attr = $this->applicaRegola($attr, null, $stage->code, $dati['due_date'] ?? null, $dati['completed_at'] ?? null, $scadenzaPredefinita);
        $this->controllaPadre($dati['parent_task_id'] ?? null, null);

        return DB::transaction(function () use ($utente, $dati, $attr, $stage) {
            $task = Task::create($attr + ['user_id' => $utente?->id, 'stage_id' => $stage->id, 'assigned_to' => $dati['assigned_to'] ?? null]);

            if (!empty($dati['collaboratori'])) {
                $task->collaborators()->sync($dati['collaboratori']);
            }
            if (!empty($dati['tag'])) {
                $this->tag->applica(null, $task, $dati['tag']);
            }
            if (!empty($dati['goal_ids'])) {
                $task->goals()->sync($dati['goal_ids']);
                $this->risincronizza($task);
            }

            RegistroScrittura::annota($utente, 'crea', 'task', $task->id, "Task «{$task->title}» creato in {$stage->code}");

            return $task->fresh();
        });
    }

    /** Modifica parziale: solo le chiavi presenti in $dati, tra i CAMPI. Lo stage si cambia con spostaStage. */
    public function modifica(?User $utente, Task $task, array $dati): Task
    {
        $dati = $this->normalizza(array_intersect_key($dati, array_flip(self::CAMPI)));
        if (!$dati) {
            return $task;
        }
        $this->valida($dati, false, $task);

        $code = $task->stage?->code;
        if (array_key_exists('due_date', $dati) && !$dati['due_date'] && in_array($code, self::ESECUZIONE, true)) {
            throw ValidationException::withMessages(['due_date' => "Un task in «{$task->stage->label}» deve avere una scadenza."]);
        }
        if (array_key_exists('parent_task_id', $dati)) {
            $this->controllaPadre($dati['parent_task_id'], $task);
        }

        $originali = $task->getOriginal();
        $task->update($dati);
        $this->annotaModifica($utente, $task, $originali);

        return $task->fresh();
    }

    /**
     * La regola unica di stage: scadenza obbligatoria per todo, doing, in_attesa (con $scadenzaPredefinita
     * diventa oggi); data di chiusura impostata entrando in done o archiviato se manca, tolta solo tornando
     * a uno stage aperto.
     */
    public function spostaStage(?User $utente, Task $task, string $codice, ?string $scadenza = null, ?string $chiusoIl = null, bool $scadenzaPredefinita = false): Task
    {
        $stage = Stage::where('code', $codice)->first() ?? throw new RegolaDominio("Stage sconosciuto: {$codice}.");
        $prima = $task->stage?->code;

        $attr = $this->applicaRegola(['stage_id' => $stage->id], $task, $stage->code, $scadenza ?: null, $chiusoIl ?: null, $scadenzaPredefinita);

        $originali = $task->getOriginal();
        $task->update($attr);
        RegistroScrittura::annota($utente, 'sposta_stage', 'task', $task->id,
            "Task «{$task->title}» da {$prima} a {$stage->code}", RegistroScrittura::differenze($task, $originali));

        return $task->fresh();
    }

    /** Responsabile e collaboratori: campi operativi, non risincronizzano Mnemosyne. null lascia invariato. */
    public function assegna(?User $utente, Task $task, ?int $responsabile, ?array $collaboratori = null, bool $cambiaResponsabile = true): Task
    {
        Validator::make(
            ['assigned_to' => $responsabile, 'collaboratori' => $collaboratori],
            ['assigned_to' => 'nullable|integer|exists:users,id', 'collaboratori' => 'nullable|array', 'collaboratori.*' => 'integer|exists:users,id'],
        )->validate();

        $prima = ['responsabile' => $task->assigned_to, 'collaboratori' => $task->collaborators()->pluck('users.id')->all()];

        if ($cambiaResponsabile) {
            $task->update(['assigned_to' => $responsabile]);
        }
        if ($collaboratori !== null) {
            $task->collaborators()->sync($collaboratori);
        }

        $dopo = ['responsabile' => $task->assigned_to, 'collaboratori' => $task->collaborators()->pluck('users.id')->all()];
        if ($prima != $dopo) {
            RegistroScrittura::annota($utente, 'assegna', 'task', $task->id, "Task «{$task->title}»: assegnazione aggiornata",
                ['assegnazione' => [$prima, $dopo]]);
        }

        return $task->fresh();
    }

    public function collegaGoal(?User $utente, Task $task, array $aggiungi = [], array $togli = []): Task
    {
        Validator::make(compact('aggiungi', 'togli'), ['aggiungi.*' => 'integer|exists:goals,id', 'togli.*' => 'integer'])->validate();

        $prima = $task->goals()->pluck('goals.id')->all();
        if ($aggiungi) $task->goals()->syncWithoutDetaching($aggiungi);
        if ($togli)    $task->goals()->detach($togli);
        $dopo = $task->goals()->pluck('goals.id')->all();

        if ($prima != $dopo) {
            // I goal sono relazioni del nodo in Mnemosyne, che l'observer non vede.
            $this->risincronizza($task);
            RegistroScrittura::annota($utente, 'collega_goal', 'task', $task->id, "Task «{$task->title}»: goal aggiornati", ['goal' => [$prima, $dopo]]);
        }

        return $task->fresh();
    }

    /** Voci di diario collegate (task_entry): non sono relazioni del nodo, nessuna risincronizzazione. */
    public function collegaVoci(?User $utente, Task $task, array $aggiungi = [], array $togli = []): Task
    {
        Validator::make(compact('aggiungi', 'togli'), ['aggiungi.*' => 'integer|exists:entries,id', 'togli.*' => 'integer'])->validate();

        $prima = $task->entries()->pluck('entries.id')->all();
        if ($aggiungi) $task->entries()->syncWithoutDetaching($aggiungi);
        if ($togli)    $task->entries()->detach($togli);
        $dopo = $task->entries()->pluck('entries.id')->all();

        if ($prima != $dopo) {
            RegistroScrittura::annota($utente, 'collega_voci', 'task', $task->id, "Task «{$task->title}»: voci collegate aggiornate", ['voci' => [$prima, $dopo]]);
        }

        return $task->fresh();
    }

    /**
     * Eliminazione recuperabile. Sotto-task non eliminati: 'errore' (predefinito, li elenca), 'includi'
     * (elimina anche loro, a ogni livello), 'lascia' (restano come sono: è il comportamento dell'interfaccia).
     * Restituisce gli id eliminati.
     */
    public function elimina(?User $utente, Task $task, string $sottotask = 'errore'): array
    {
        $discendenti = $this->discendenti($task);

        if ($discendenti->isNotEmpty() && $sottotask === 'errore') {
            throw new RegolaDominio("Il task «{$task->title}» ha " . $discendenti->count() . ' sotto-task: '
                . $discendenti->take(10)->map(fn ($t) => "«{$t->title}» (id {$t->id})")->implode(', ')
                . '. Per eliminarli insieme serve una conferma esplicita.');
        }

        $daEliminare = $sottotask === 'includi' ? $discendenti->push($task) : collect([$task]);

        DB::transaction(function () use ($utente, $daEliminare) {
            foreach ($daEliminare as $t) {
                $t->delete();
                RegistroScrittura::annota($utente, 'elimina', 'task', $t->id, "Task «{$t->title}» eliminato (recuperabile)");
            }
        });

        return $daEliminare->pluck('id')->all();
    }

    public function ripristina(?User $utente, Task $task): Task
    {
        if (!$task->trashed()) {
            return $task;
        }
        $task->restore();
        RegistroScrittura::annota($utente, 'ripristina', 'task', $task->id, "Task «{$task->title}» ripristinato");

        return $task->fresh();
    }

    // ---- regole ----

    private function applicaRegola(array $attr, ?Task $task, string $code, ?string $scadenza, ?string $chiusoIl, bool $scadenzaPredefinita): array
    {
        if (in_array($code, self::ESECUZIONE, true)) {
            $scadenzaAttuale = $scadenza ?? $task?->due_date?->toDateString();
            if (!$scadenzaAttuale) {
                if (!$scadenzaPredefinita) {
                    $label = Stage::where('code', $code)->value('label') ?? $code;
                    throw ValidationException::withMessages(['due_date' => "Per mettere un task in «{$label}» serve una scadenza."]);
                }
                $scadenzaAttuale = Carbon::today('Europe/Rome')->toDateString();
            }
            $attr['due_date'] = $scadenzaAttuale;
        } elseif ($scadenza) {
            $attr['due_date'] = $scadenza;
        }

        if (in_array($code, self::CHIUSI, true)) {
            if ($chiusoIl) {
                $attr['completed_at'] = $chiusoIl;
            } elseif (!$task?->completed_at) {
                $attr['completed_at'] = $attr['completed_at'] ?? now();
            }
        } else {
            $attr['completed_at'] = null;
        }

        return $attr;
    }

    private function controllaPadre(?int $padreId, ?Task $task): void
    {
        if (!$padreId) {
            return;
        }
        $padre = Task::find($padreId) ?? throw new RegolaDominio("Il task padre {$padreId} non esiste o è eliminato.");
        if ($task && ($padre->id === $task->id || $this->discendenti($task)->contains('id', $padre->id))) {
            throw new RegolaDominio("Il task «{$padre->title}» non può essere padre di «{$task->title}»: si creerebbe un ciclo.");
        }
    }

    private function discendenti(Task $task)
    {
        $tutti  = collect();
        $fronte = collect([$task->id]);
        while ($fronte->isNotEmpty()) {
            $figli  = Task::whereIn('parent_task_id', $fronte)->whereNotIn('id', $tutti->pluck('id')->push($task->id))->get();
            $tutti  = $tutti->merge($figli);
            $fronte = $figli->pluck('id');
        }

        return $tutti;
    }

    private function stage(array $dati): Stage
    {
        if (!empty($dati['stage_id'])) {
            return Stage::find($dati['stage_id']) ?? throw new RegolaDominio("Stage {$dati['stage_id']} sconosciuto.");
        }
        $code = $dati['stage'] ?? 'idea';

        return Stage::where('code', $code)->first() ?? throw new RegolaDominio("Stage sconosciuto: {$code}.");
    }

    private function valida(array $dati, bool $creazione, ?Task $task = null): void
    {
        $regole = [
            'title'          => ($creazione ? 'required' : 'sometimes') . '|string|max:255',
            'description'    => 'nullable|string',
            'area_id'        => 'nullable|integer|exists:areas,id',
            'parent_task_id' => 'nullable|integer',
            'priority'       => ['nullable', Rule::in(['bassa', 'media', 'alta'])],
            'executor'       => ['nullable', Rule::in(['una_persona', 'team', 'professionista', 'impresa'])],
            'due_date'       => 'nullable|date',
            'start_date'     => 'nullable|date',
            'completed_at'   => 'nullable|date',
            'work_estimate'  => 'nullable|numeric|min:0',
            'cost_min'       => 'nullable|numeric|min:0',
            'cost_max'       => 'nullable|numeric|min:0',
            'cost_real'      => 'nullable|numeric|min:0',
            'cost_basis'     => ['nullable', Rule::in(['stima', 'preventivo', 'reale'])],
            'assigned_to'    => 'nullable|integer|exists:users,id',
            'collaboratori'  => 'nullable|array',
            'collaboratori.*'=> 'integer|exists:users,id',
            'tag'            => 'nullable|array',
            'tag.*'          => 'string|max:100',
            'goal_ids'       => 'nullable|array',
            'goal_ids.*'     => 'integer|exists:goals,id',
        ];
        Validator::make($dati, $regole, [], ['title' => 'titolo', 'due_date' => 'scadenza'])->validate();

        $min = array_key_exists('cost_min', $dati) ? $dati['cost_min'] : $task?->cost_min;
        $max = array_key_exists('cost_max', $dati) ? $dati['cost_max'] : $task?->cost_max;
        if ($min !== null && $max !== null && (float) $min > (float) $max) {
            throw ValidationException::withMessages(['cost_max' => 'Il costo massimo non può essere inferiore al minimo.']);
        }
    }

    /** Stringhe vuote come null, testi ripuliti dagli spazi ai bordi. */
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

    private function risincronizza(Task $task): void
    {
        if (config('services.mnemosyne.sync', true)) {
            SyncTaskToMnemosyne::dispatch($task->id)->afterCommit();
        }
    }

    private function annotaModifica(?User $utente, Task $task, array $originali): void
    {
        $diff = RegistroScrittura::differenze($task, $originali);
        if ($diff) {
            RegistroScrittura::annota($utente, 'modifica', 'task', $task->id,
                "Task «{$task->title}» modificato: " . implode(', ', array_keys($diff)), $diff);
        }
    }
}
