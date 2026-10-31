<?php

namespace App\Queries\Entita;

use App\Exceptions\RegolaDominio;
use App\Models\Area;
use App\Queries\AlberoAree;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Un'entità di dominio vista in lettura: quali filtri accetta, come si costruisce la
 * query, come appare in un elenco e nel dettaglio. Nessuna dipendenza da Livewire o
 * dalla richiesta HTTP: la usano il server MCP e, un domani, un'API.
 */
abstract class Entita
{
    public const LIMITE_PREDEFINITO = 20;
    public const LIMITE_MASSIMO     = 100;
    public const TRONCA             = 200;

    /** @return class-string<Model> */
    abstract protected function model(): string;

    /** Nomi dei filtri ammessi per questa entità. */
    abstract public function filtri(): array;

    abstract protected function applicaFiltri(Builder $q, array $filtri): void;

    abstract protected function ordina(Builder $q): void;

    abstract public function riga(Model $m): array;

    abstract public function dettaglio(Model $m): array;

    abstract public function url(Model $m): ?string;

    protected function relazioniElenco(): array
    {
        return [];
    }

    protected function relazioniDettaglio(): array
    {
        return $this->relazioniElenco();
    }

    /** @return array{totale:int, limite:int, offset:int, elementi:array, extra?:array} */
    public function elenca(array $filtri, int $limite = self::LIMITE_PREDEFINITO, int $offset = 0, bool $archiviati = false): array
    {
        $this->controllaFiltri($filtri);

        $limite = max(1, min($limite, self::LIMITE_MASSIMO));
        $offset = max(0, $offset);

        $q = $this->base($archiviati);
        $this->applicaFiltri($q, $filtri);

        $totale = (clone $q)->count();
        $extra  = $this->extraElenco(clone $q);

        $this->ordina($q);
        $elementi = $q->with($this->relazioniElenco())->skip($offset)->take($limite)->get()
            ->map(fn (Model $m) => $this->conComuni($m, $this->riga($m)))
            ->all();

        return array_filter(compact('totale', 'limite', 'offset', 'elementi', 'extra'), fn ($v) => $v !== null);
    }

    public function leggi(int $id, bool $archiviati = false): array
    {
        $m = $this->base($archiviati)->with($this->relazioniDettaglio())->find($id);
        if (!$m) {
            throw new RegolaDominio("Nessun elemento di tipo {$this->nome()} con id {$id}" . ($archiviati ? '.' : " (se è archiviato, riprova con includi_archiviati)."));
        }

        return $this->conComuni($m, $this->dettaglio($m));
    }

    /** Dati aggregati sull'intero elenco filtrato (per esempio la somma delle spese). */
    protected function extraElenco(Builder $q): ?array
    {
        return null;
    }

    public function nome(): string
    {
        return array_search(static::class, Catalogo::ENTITA, true) ?: class_basename(static::class);
    }

    private function base(bool $archiviati): Builder
    {
        $model = $this->model();
        $q     = $model::query();

        if ($archiviati && in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            $q->withTrashed();
        }

        return $q;
    }

    private function controllaFiltri(array $filtri): void
    {
        $nonAmmessi = array_diff(array_keys($filtri), $this->filtri());
        if ($nonAmmessi) {
            $ammessi = $this->filtri() ? implode(', ', $this->filtri()) : 'nessuno';
            throw new RegolaDominio('Filtri non validi per ' . $this->nome() . ': ' . implode(', ', $nonAmmessi) . ". Filtri ammessi: {$ammessi}.");
        }
    }

    private function conComuni(Model $m, array $dati): array
    {
        $comuni = ['id' => $m->getKey(), 'url' => $this->url($m)];
        if (method_exists($m, 'trashed') && $m->trashed()) {
            $comuni['archiviato_il'] = $m->deleted_at->toDateString();
        }

        return $comuni + $dati;
    }

    // ---- aiuti per le sottoclassi ----

    protected function tronca(?string $testo): ?string
    {
        return $testo === null ? null : Str::limit($testo, self::TRONCA);
    }

    protected function data($valore): ?string
    {
        return $valore?->toDateString();
    }

    protected function area(?Area $area): ?array
    {
        return $area ? ['id' => $area->id, 'nome' => $area->name] : null;
    }

    protected function testo(Builder $q, array $filtri, array $campi): void
    {
        if (!empty($filtri['testo'])) {
            $term = '%' . trim($filtri['testo']) . '%';
            $q->where(function ($qq) use ($campi, $term) {
                foreach ($campi as $campo) {
                    $qq->orWhere($campo, 'like', $term);
                }
            });
        }
    }

    protected function filtroArea(Builder $q, array $filtri, string $colonna = 'area_id'): void
    {
        if (!empty($filtri['area_id'])) {
            $q->whereIn($colonna, $this->areaIds((int) $filtri['area_id'], (bool) ($filtri['includi_sottoaree'] ?? false)));
        }
    }

    protected function filtroDate(Builder $q, array $filtri, string $colonna): void
    {
        if (!empty($filtri['dal'])) {
            $q->whereDate($colonna, '>=', $filtri['dal']);
        }
        if (!empty($filtri['al'])) {
            $q->whereDate($colonna, '<=', $filtri['al']);
        }
    }

    /** L'area e, se richiesto, tutte le sue discendenti. */
    protected function areaIds(int $id, bool $sottoaree): array
    {
        return $sottoaree ? AlberoAree::conDiscendenti($id) : [$id];
    }
}
