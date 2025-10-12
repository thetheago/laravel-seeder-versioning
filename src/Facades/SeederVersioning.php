<?php

namespace Thetheago\SeederVersioning\Facades;

use Illuminate\Support\Facades\Facade;

class SeederVersioning extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'seeder-versioning';
    }
}
