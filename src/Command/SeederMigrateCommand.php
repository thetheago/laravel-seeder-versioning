<?php

namespace Thetheago\SeederVersioning\Command;

use Illuminate\Console\Command;
use RuntimeException;
use Thetheago\SeederVersioning\Command\Concerns\RendersSeederPreview;
use Thetheago\SeederVersioning\Services\SeederRunner;
use Thetheago\SeederVersioning\Services\SeederVersioningService;

class SeederMigrateCommand extends Command
{
    use RendersSeederPreview;

    protected $signature = 'seed:migrate
                            {--hash-only : Just generate the hash of migrations, without running them.}
                            {--pretend : Show the seeders that would run, without running them.}
                            {--force : Run without asking for confirmation, also in production.}';
    protected $description = 'Run seeders that have been modified.';

    public function __construct(protected SeederVersioningService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $pretend = $this->option('pretend');

        try {
            // --pretend is read-only: it does not create the versioning tables.
            if (! $pretend) {
                $this->service->ensureSetup();
            }

            $pending = $this->pendingSeeders($this->service->seederStatuses());
        } catch (RuntimeException $e) {
            $this->warn("⚠️  {$e->getMessage()}\n");
            return self::FAILURE;
        }

        if (! $pending) {
            $this->info('No new or modified seeders.');
            return self::SUCCESS;
        }

        $hashOnly = $this->option('hash-only');
        $action = $hashOnly ? 'registered without running (hash-only)' : 'run';

        $this->renderSeederTable($pending);

        if ($pretend) {
            $this->line($this->pendingSummary($pending) . " would be {$action}.");
            return self::SUCCESS;
        }

        if (! $this->confirmToRun($pending, $action)) {
            $this->components->warn('Command cancelled.');
            return self::FAILURE;
        }

        if ($hashOnly) {
            $this->info('Generating seeders hashing (hash-only).');
            $this->newLine();
        } else {
            $this->info('Running seeders...');
            $this->newLine();
        }

        SeederRunner::setCommand($this);
        $this->service->runVersionedSeeders($hashOnly);
        $this->newLine();
        $this->info('Done.');
        return self::SUCCESS;
    }

    /**
     * Asks before running, unless --force. Without a terminal (CI) it runs without asking, except in
     * production, where it is cancelled like db:seed.
     *
     * @param array<int, array<string, mixed>> $pending
     */
    protected function confirmToRun(array $pending, string $action): bool
    {
        if ($this->option('force')) {
            return true;
        }

        $production = $this->laravel->environment() === 'production';

        if (! $this->input->isInteractive()) {
            return ! $production;
        }

        if ($production) {
            $this->components->alert('Application In Production');
        }

        return $this->confirm($this->pendingSummary($pending) . " will be {$action}. Do you want to continue?", false);
    }
}
