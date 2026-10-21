<?php

namespace Thetheago\SeederVersioning\Exceptions;

use RuntimeException;
use Throwable;

class SeederFailedException extends RuntimeException
{
    public function __construct(public readonly string $seeder, Throwable $previous)
    {
        parent::__construct("Seeder {$seeder} failed: {$previous->getMessage()}", 0, $previous);
    }
}
