<?php

namespace Thetheago\SeederVersioning\Services;

use Illuminate\Console\Command;
use Illuminate\Database\Seeder;

class SeederRunner
{
    protected static ?Command $command = null;

    /**
     * The command whose output the seeders use, like db:seed does.
     */
    public static function setCommand(?Command $command): void
    {
        static::$command = $command;
    }

    /**
     * Runs a seeder the way db:seed does: with the container and the command set, so
     * $this->call(), $this->command and dependencies injected into run() work.
     */
    public static function run(string $class): void
    {
        $instance = app($class);

        if ($instance instanceof Seeder) {
            $instance->setContainer(app());

            if (static::$command) {
                $instance->setCommand(static::$command);
            }

            $instance->__invoke();

            return;
        }

        if (is_object($instance) && method_exists($instance, 'run')) {
            app()->call([$instance, 'run']);
        }
    }
}
