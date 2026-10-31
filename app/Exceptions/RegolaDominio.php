<?php

namespace App\Exceptions;

use RuntimeException;

/** Violazione di una regola del dominio: il messaggio è rivolto a chi usa l'app, non a uno sviluppatore. */
class RegolaDominio extends RuntimeException
{
}
