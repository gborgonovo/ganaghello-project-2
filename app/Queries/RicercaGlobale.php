<?php

namespace App\Queries;

use App\Models\Entry;
use App\Models\Inspiration;
use App\Models\Note;
use App\Models\Post;
use App\Models\Task;
use Illuminate\Support\Collection;

/**
 * Ricerca testuale sulle entità di dominio, condivisa da GlobalSearch e dal tool MCP `cerca`.
 * Restituisce le collezioni di model per tipo; la presentazione la decide chi chiama.
 */
class RicercaGlobale
{
    public const TIPI = ['task', 'voce', 'nota', 'ispirazione', 'post'];

    /** @return array<string, Collection> tipo => model trovati (solo i tipi con risultati) */
    public function cerca(string $testo, ?array $tipi = null, ?int $limite = null): array
    {
        $testo = trim($testo);
        if (mb_strlen($testo) < 2) {
            return [];
        }

        $term = '%' . $testo . '%';
        $tipi ??= self::TIPI;
        $risultati = [];

        $query = [
            'task' => fn () => Task::where(fn ($q) => $q->where('title', 'like', $term)->orWhere('description', 'like', $term))
                ->with('stage')->orderByDesc('updated_at')->limit($limite ?? 5),
            'voce' => fn () => Entry::where('content', 'like', $term)
                ->orderByDesc('entry_date')->limit($limite ?? 4),
            'nota' => fn () => Note::where(fn ($q) => $q->where('title', 'like', $term)->orWhere('content', 'like', $term))
                ->orderByDesc('updated_at')->limit($limite ?? 4),
            'ispirazione' => fn () => Inspiration::where(fn ($q) => $q->where('title', 'like', $term)->orWhere('description', 'like', $term))
                ->orderByDesc('id')->limit($limite ?? 4),
            'post' => fn () => Post::where(fn ($q) => $q->where('title', 'like', $term)->orWhere('excerpt', 'like', $term)->orWhere('content', 'like', $term))
                ->orderByDesc('published_at')->limit($limite ?? 4),
        ];

        foreach (self::TIPI as $tipo) {
            if (!in_array($tipo, $tipi, true)) {
                continue;
            }
            $trovati = $query[$tipo]()->get();
            if ($trovati->isNotEmpty()) {
                $risultati[$tipo] = $trovati;
            }
        }

        return $risultati;
    }
}
