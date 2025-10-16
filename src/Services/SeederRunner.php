<?php

namespace Thetheago\SeederVersioning\Services;

class SeederRunner
{
    public static function run(string $class): void
    {
        $instance = app($class);

        if (is_object($instance) && method_exists($instance, 'run')) {
            $instance->run();
        }
    }
}
