<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Entry;
use App\Models\Goal;
use App\Models\Stage;
use App\Models\Task;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MnemosyneService
{
    private string $endpoint;
    private string $apiKey;
    private string $project;
    private string $scope;

    /** @var int[]|null memo degli stage chiusi, per resolveNode */
    private ?array $doneStageIds = null;

    public function __construct()
    {
        $this->endpoint = config('services.mnemosyne.endpoint');
        $this->apiKey   = config('services.mnemosyne.api_key');
        $this->project  = config('services.mnemosyne.project');
        $this->scope    = config('services.mnemosyne.scope');
    }

    /**
     * Chiama GET /briefing e filtra i nodi del progetto Ganaghello.
     * Restituisce ['dormant' => [...], 'hot' => [...]] oppure null se il servizio e' irraggiungibile.
     */
    public function briefing(): ?array
    {
        try {
            $response = Http::withHeader('X-API-Key', $this->apiKey)
                ->timeout(5)
                ->get("{$this->endpoint}/briefing");

            if (!$response->successful()) {
                Log::warning('Mnemosyne briefing non disponibile', ['status' => $response->status()]);
                return null;
            }

            $data = $response->json();

            return [
                'dormant' => $this->filterByProject($data['dormant'] ?? []),
                'hot'     => $this->filterByProject($data['hot'] ?? []),
            ];
        } catch (\Throwable $e) {
            Log::warning('Mnemosyne non raggiungibile: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Risolve il nome di un nodo Mnemosyne nell'entita' di dominio corrispondente.
     * Ritorna ['label', 'url', 'updated_at'] oppure null se non trovata
     * (o se e' un task gia' chiuso). Usato da cruscotto e digest per i link.
     */
    public function resolveNode(string $name): ?array
    {
        if ($name === '') {
            return null;
        }

        $doneIds = $this->doneStageIds ??= Stage::whereIn('code', ['done', 'archiviato'])->pluck('id')->all();

        $task = Task::where('mnemosyne_node_name', $name)
            ->whereNotIn('stage_id', $doneIds)
            ->whereNull('deleted_at')
            ->first();
        if ($task) {
            return ['label' => $task->title, 'url' => route('tasks.show', $task), 'updated_at' => $task->updated_at];
        }

        $goal = Goal::where('mnemosyne_node_name', $name)->first();
        if ($goal) {
            return ['label' => $goal->title, 'url' => route('goals'), 'updated_at' => $goal->updated_at];
        }

        $area = Area::where('mnemosyne_node_name', $name)->first();
        if ($area) {
            return ['label' => $area->name, 'url' => route('aree.show', $area), 'updated_at' => $area->updated_at];
        }

        $entry = Entry::where('mnemosyne_node_name', $name)->first();
        if ($entry) {
            return ['label' => $entry->title, 'url' => route('diario.show', $entry), 'updated_at' => $entry->updated_at];
        }

        return null;
    }

    private function filterByProject(array $nodes): array
    {
        return array_values(array_filter($nodes, function ($node) {
            $props = $node['properties'] ?? [];
            $folder = $props['folder'] ?? $props['project'] ?? '';
            return stripos($folder, $this->project) !== false;
        }));
    }

    // =====================================================================
    // SCRITTURA (Fase 14)
    // I metodi lanciano eccezione su errore HTTP: i job li intercettano per il retry.
    // =====================================================================

    /** True se la sincronizzazione in scrittura e' attiva e configurata. */
    public function enabled(): bool
    {
        return (bool) config('services.mnemosyne.sync', true) && !empty($this->apiKey);
    }

    /**
     * POST /tasks (upsert per nome). Ritorna il corpo JSON (con `name` canonico).
     * `$status` e' il codice dello stage: Mnemosyne lo salva nel frontmatter `status`,
     * ed e' l'unico modo per farlo (il marcatore in descrizione NON lo aggiorna).
     */
    public function pushTask(string $name, string $description, ?string $deadline, string $relations, ?string $status = null, string $scope = 'Private'): array
    {
        return $this->write('/tasks', [
            'name'        => $name,
            'description' => $description,
            'deadline'    => $deadline,
            'status'      => $status,
            'folder'      => $this->project,
            'scopes'      => $scope,
            'relations'   => $relations,
        ]);
    }

    /** POST /goals (upsert per nome). */
    public function pushGoal(string $name, string $description, ?string $deadline, string $relations, string $scope = 'Private'): array
    {
        return $this->write('/goals', [
            'name'        => $name,
            'description' => $description,
            'deadline'    => $deadline,
            'folder'      => $this->project,
            'scopes'      => $scope,
            'relations'   => $relations,
        ]);
    }

    /** POST /nodes (upsert per nome). Per Journal e Area. */
    public function pushNode(string $name, string $content, string $nodeType, string $relations, string $scope = 'Private'): array
    {
        return $this->write('/nodes', [
            'name'      => $name,
            'content'   => $content,
            'node_type' => $nodeType,
            'scope'     => $scope,
            'folder'    => $this->project,
            'relations' => $relations,
        ]);
    }

    /** DELETE /nodes/{name}. Un 404 (nodo gia' assente) e' trattato come successo. */
    public function deleteNode(string $name, string $scope = 'Private'): void
    {
        $url = $this->endpoint . '/nodes/' . rawurlencode($name) . '?scopes=' . rawurlencode($scope);

        $response = Http::withHeader('X-API-Key', $this->apiKey)
            ->timeout(15)
            ->delete($url);

        if ($response->status() === 404) {
            return;
        }

        $response->throw();
    }

    private function write(string $path, array $payload): array
    {
        // Rimuove i campi vuoti/null (deadline assente, relazioni vuote, ...).
        $payload = array_filter($payload, fn ($v) => $v !== null && $v !== '');

        $response = Http::withHeader('X-API-Key', $this->apiKey)
            ->acceptJson()
            ->timeout(15)
            ->post($this->endpoint . $path, $payload)
            ->throw();

        return $response->json() ?? [];
    }
}
