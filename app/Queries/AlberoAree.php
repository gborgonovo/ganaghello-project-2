<?php

namespace App\Queries;

use App\Models\Area;

class AlberoAree
{
    /** L'area seguita da tutte le sue discendenti, lungo parent_area_id. */
    public static function conDiscendenti(int $id): array
    {
        $padri = Area::pluck('parent_area_id', 'id');
        $ids   = [$id];
        for ($i = 0; $i < count($ids); $i++) {
            foreach ($padri as $figlio => $padre) {
                if ($padre === $ids[$i] && !in_array($figlio, $ids, true)) {
                    $ids[] = $figlio;
                }
            }
        }

        return $ids;
    }
}
