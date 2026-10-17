<?php

namespace Thetheago\SeederVersioning\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;
use Thetheago\SeederVersioning\Services\SeederVersioningService;

class SeederStatusCommand extends Command
{
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

        $pending = array_filter(
            $statuses,
            fn (array $status) => $status['status'] !== SeederVersioningService::STATUS_UP_TO_DATE
        );

        $rows = $this->option('all') ? $statuses : $pending;

        if ($rows) {
            $this->table(['Seeder', 'Status', 'Hash'], array_map(
                fn (array $status) => [$status['seeder'], $status['status'], $this->shortHash($status['hash'])],
                $rows
            ));
        }

        if (! $pending) {
            $this->info('No new or modified seeders.');
            return self::SUCCESS;
        }

        $count = count($pending);
        $this->line($count . ' ' . Str::plural('seeder', $count) . ' pending. Run <comment>php artisan seed:migrate</comment> to execute them.');

        return self::SUCCESS;
    }

    protected function shortHash(string $hash): string
    {
        return substr($hash, 0, 12);
    }
}
