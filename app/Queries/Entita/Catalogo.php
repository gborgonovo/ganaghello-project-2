<?php

namespace App\Queries\Entita;

use App\Exceptions\RegolaDominio;

/** Le entità di dominio leggibili, per nome. */
class Catalogo
{
    public const ENTITA = [
        'task'          => TaskEntita::class,
        'aggiornamento' => AggiornamentoEntita::class,
        'goal'          => GoalEntita::class,
        'area'          => AreaEntita::class,
        'nota'          => NotaEntita::class,
        'tag'           => TagEntita::class,
        'spesa'         => SpesaEntita::class,
        'voce'          => VoceEntita::class,
        'post'          => PostEntita::class,
        'ispirazione'   => IspirazioneEntita::class,
    ];

    public static function nomi(): array
    {
        return array_keys(self::ENTITA);
    }

    public static function per(string $nome): Entita
    {
        $classe = self::ENTITA[$nome] ?? throw new RegolaDominio("Entità sconosciuta: {$nome}. Entità valide: " . implode(', ', self::nomi()) . '.');

        return app($classe);
    }

    /** Tutti i filtri usati da almeno un'entità, per lo schema del tool `elenca`. */
    public static function tuttiIFiltri(): array
    {
        return collect(self::ENTITA)->flatMap(fn ($c) => app($c)->filtri())->unique()->values()->all();
    }
}
