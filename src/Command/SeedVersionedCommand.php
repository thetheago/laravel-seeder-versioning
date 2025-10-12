<?php

namespace Thetheago\SeederVersioning\Command;

use Illuminate\Console\Command;
use Thetheago\SeederVersioning\Facades\SeederVersioning;

class SeedVersionedCommand extends Command
{
    protected $signature = 'seed:migrate';
    protected $description = 'Run seeders that have been modified.';

    public function handle(): int
    {
        $this->info('Running versioned seeders...');
        SeederVersioning::runVersionedSeeders();
        $this->info('Done.');
        return self::SUCCESS;
    }
}
