<?php

namespace Thetheago\SeederVersioning\Command;

use Illuminate\Console\Command;
use RuntimeException;
use Thetheago\SeederVersioning\Command\Concerns\RendersSeederPreview;
use Thetheago\SeederVersioning\Services\SeederVersioningService;

class SeederStatusCommand extends Command
{
    use RendersSeederPreview;

    protected $signature = 'seed:status
                            {--all : Also list the seeders that are up to date.}';
    protected $description = 'Show the last seeder run and the seeders that have not been run yet.';

    public function __construct(protected SeederVersioningService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $statuses = $this->service->seederStatuses();
        } catch (RuntimeException $e) {
            $this->warn("⚠️  {$e->getMessage()}");
            return self::FAILURE;
        }

        $last = $this->service->lastRanSeeder();

        if ($last) {
            $this->line("Last seeder run: <info>{$last->seeder}</info> (<comment>{$this->shortHash($last->hash)}</comment>) at {$last->ran_at}");
        } else {
            $this->line('No seeders have been run yet.');
        }

        $this->newLine();

        $pending = $this->pendingSeeders($statuses);
        $rows = $this->option('all') ? $statuses : $pending;

        if ($rows) {
            $this->renderSeederTable($rows);
        }

        if (! $pending) {
            $this->info('No new or modified seeders.');
            return self::SUCCESS;
        }

        $this->line($this->pendingSummary($pending) . ' pending. Run <comment>php artisan seed:migrate</comment> to execute them.');

        return self::SUCCESS;
    }
}
