<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MnemosyneService
{
    private string $endpoint;
    private string $apiKey;
    private string $project;
    private string $scope;

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

    /** POST /tasks (upsert per nome). Ritorna il corpo JSON (con `name` canonico). */
    public function pushTask(string $name, string $description, ?string $deadline, string $relations, string $scope = 'Private'): array
    {
        return $this->write('/tasks', [
            'name'        => $name,
            'description' => $description,
            'deadline'    => $deadline,
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
