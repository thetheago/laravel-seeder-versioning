<?php

namespace Thetheago\SeederVersioning\Command;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Thetheago\SeederVersioning\Services\SeederVersioningService;

class SeederMigrateCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'seed:migrate
                            {--hash-only : Just generate the hash of migrations, without running them.}
                            {--force : Force the operation to run when in production.}';
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

        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        $hashOnly = $this->option('hash-only');

        if ($hashOnly) {
            $this->info('Generating seeders hashing (hash-only).');
            $this->newLine();
        } else {
            $this->info('Running seeders...');
            $this->newLine();
        }

        $this->service->runVersionedSeeders($hashOnly);
        $this->newLine();
        $this->info('Done.');
        return self::SUCCESS;
    }
}
