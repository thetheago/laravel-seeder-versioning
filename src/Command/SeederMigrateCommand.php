<?php

namespace Thetheago\SeederVersioning\Command;

use Illuminate\Console\Command;
use Thetheago\SeederVersioning\Facades\SeederVersioning;
use Thetheago\SeederVersioning\Services\SeederVersioningService;

class SeederMigrateCommand extends Command
{
    protected $signature = 'seed:migrate
                            {--hash-only : Just generate the hash of migrations, without running them.}';
    protected $description = 'Run seeders that have been modified.';

    public function __construct(protected SeederVersioningService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $this->service->ensureSetup();
        } catch (\RuntimeException $e) {
            $this->warn("⚠️  {$e->getMessage()}\n");
            return self::FAILURE;
        }

        $hashOnly = $this->option('hash-only');

        if ($hashOnly) {
            $this->info('Generating seeders hashing (hash-only).');
        } else {
            $this->info('Running seeders...');
        }

        SeederVersioning::runVersionedSeeders($hashOnly);
        $this->info('Done.');
        return self::SUCCESS;
    }
}
