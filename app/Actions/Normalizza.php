<?php

namespace App\Actions;

class Normalizza
{
    /** Testi ripuliti dagli spazi ai bordi, stringhe vuote come null. */
    public static function testi(array $dati): array
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
