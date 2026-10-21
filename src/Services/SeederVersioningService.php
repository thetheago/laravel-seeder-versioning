<?php

namespace Thetheago\SeederVersioning\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Console\Output\ConsoleOutput;

class SeederVersioningService
{
    public const STATUS_NEW = 'new';
    public const STATUS_MODIFIED = 'modified';
    public const STATUS_UP_TO_DATE = 'up to date';

    protected const VERSIONS_MIGRATION = '2025_01_01_000000_create_seeder_versions_table.php';
    protected const HISTORY_MIGRATION = '2025_01_02_000000_create_seeder_version_history_table.php';

    protected string $table;
    protected string $historyTable;
    protected SeederRunner $seederRunner;
    protected SeederRiskAnalyzer $riskAnalyzer;
    protected ConsoleOutput $output;

    public function __construct(SeederRunner $seederRunner, ?SeederRiskAnalyzer $riskAnalyzer = null)
    {
        $this->table = config('seeder-versioning.table', 'seeder_versions');
        $this->historyTable = config('seeder-versioning.history_table', 'seeder_version_history');
        $this->seederRunner = $seederRunner;
        $this->riskAnalyzer = $riskAnalyzer ?? new SeederRiskAnalyzer();
        $this->output = new ConsoleOutput();
    }

    /**
     * The config file does not need to be published: mergeConfigFrom() provides the defaults.
     */
    public function ensureSetup(): void
    {
        if (! Schema::hasTable($this->table)) {
            $this->output->writeln("<fg=yellow>[SeederVersioning] </>Creating table <fg=cyan>{$this->table}</>...");
            $this->runMigration(self::VERSIONS_MIGRATION);
        }

        if (! Schema::hasTable($this->historyTable)) {
            $this->output->writeln("<fg=yellow>[SeederVersioning] </>Creating table <fg=cyan>{$this->historyTable}</>...");
            $this->runMigration(self::HISTORY_MIGRATION);
        }
    }

    public function runSeederVersionMigration(): void
    {
        $this->runMigration(self::VERSIONS_MIGRATION);
        $this->runMigration(self::HISTORY_MIGRATION);
    }

    protected function runMigration(string $migration): void
    {
        Artisan::call('migrate', [
            '--path' => __DIR__ . '/../../database/migrations/' . $migration,
            '--realpath' => true,
            '--force' => true,
        ]);

        $this->output->writeln("<fg=green>[SeederVersioning] </>Version migration executed successfully.");
    }

    /**
     * @param bool $hashOnly If true, only generates and fills the table without running the seeders
     */
    public function runVersionedSeeders(bool $hashOnly = false): void
    {
        $path = config('seeder-versioning.path', database_path('seeders'));

        if (!File::exists($path)) {
            $this->output->writeln("<fg=red>[SeederVersioning] </> ⚠️ Seeders directory not found at: <fg=yellow>{$path}</>");
            return;
        }

        $files = File::files($path);

        if (empty($files)) {
            $this->output->writeln("<fg=yellow>[SeederVersioning] </> 📭 No seeders found in directory <fg=cyan>{$path}</>.");
            return;
        }

        $anySeederRunned = false;

        $existingSeeders = DB::table($this->table)->get()->keyBy('seeder');

        foreach ($this->versionedFiles($files) as $file) {
            $seederName = $file->getFilenameWithoutExtension();
            $seeder = $this->resolveSeederClass($file);
            $hash = $this->calculateHash($file->getPathname());

            $existing = $existingSeeders->get($seederName);

            if (!$existing) {
                if (!$hashOnly) {
                    $this->output->writeln("<fg=gray> └─ Running new seeder... </> <fg=green>{$seederName}</>");
                    $this->seederRunner::run($seeder);
                    $anySeederRunned = true;
                }

                DB::table($this->table)->insert([
                    'seeder' => $seederName,
                    'hash' => $hash,
                    'ran_at' => now(),
                ]);
                $this->recordHistory($seederName, $hash, null, $hashOnly);

                $this->output->writeln("<fg=green> ✔ Seeder registered with hash:</> <fg=cyan>{$hash}</>");
                continue;
            }

            if ($existing->hash !== $hash) {
                $this->output->writeln("<fg=yellow>[SeederVersioning] </>Changes detected in seeder <fg=green>{$seederName}</>");

                if (!$hashOnly) {
                    $this->output->writeln("<fg=gray> └─ Re-running updated seeder...</>");
                    $this->seederRunner::run($seeder);
                    $anySeederRunned = true;
                }

                DB::table($this->table)->where('seeder', $seederName)->update([
                    'hash' => $hash,
                    'ran_at' => now(),
                ]);
                $this->recordHistory($seederName, $hash, $existing->hash, $hashOnly);

                $this->output->writeln("<fg=green> ✔ Seeder updated with new hash:</> <fg=cyan>{$hash}</>");
            }
        }

        if (! $anySeederRunned && ! $hashOnly) {
            $this->output->writeln("<fg=yellow>[SeederVersioning] </>No new or modified seeders to execute.");
        }
    }

    /**
     * Status of each versioned seeder, in execution order. Read-only: nothing is run or registered.
     *
     * For new and modified seeders it also returns the risks found in their current code.
     *
     * @return array<int, array{
     *     seeder: string,
     *     status: string,
     *     hash: string,
     *     risks: array<int, array{level: string, message: string}>
     * }>
     * @throws RuntimeException
     */
    public function seederStatuses(): array
    {
        $path = config('seeder-versioning.path', database_path('seeders'));

        if (! File::exists($path)) {
            throw new RuntimeException("Seeders directory not found at: {$path}");
        }

        $existingSeeders = Schema::hasTable($this->table)
            ? DB::table($this->table)->get()->keyBy('seeder')
            : collect();

        $statuses = [];

        foreach ($this->versionedFiles(File::files($path)) as $file) {
            $seederName = $file->getFilenameWithoutExtension();
            $hash = $this->calculateHash($file->getPathname());
            $existing = $existingSeeders->get($seederName);

            $status = match (true) {
                ! $existing => self::STATUS_NEW,
                $existing->hash !== $hash => self::STATUS_MODIFIED,
                default => self::STATUS_UP_TO_DATE,
            };

            // Only what is going to run is analyzed.
            $risks = $status === self::STATUS_UP_TO_DATE
                ? []
                : $this->riskAnalyzer->analyze(File::get($file->getPathname()));

            $statuses[] = [
                'seeder' => $seederName,
                'status' => $status,
                'hash' => $hash,
                'risks' => $risks,
            ];
        }

        return $statuses;
    }

    /**
     * The most recently run (or hashed) seeder, or null when none was registered yet.
     */
    public function lastRanSeeder(): ?object
    {
        if (! Schema::hasTable($this->table)) {
            return null;
        }

        return DB::table($this->table)->orderByDesc('ran_at')->orderByDesc('id')->first();
    }

    public function resolveSeederClass(SplFileInfo $file): string
    {
        $filePath = $file->getPathname();

        $contents = File::get($filePath);

        if (preg_match('/^namespace\s+(.+?);/m', $contents, $matches)) {
            $namespace = $matches[1];
        } else {
            throw new RuntimeException("No namespace found in file: {$filePath}");
        }

        if (preg_match('/class\s+(\w+)/', $contents, $matches)) {
            $className = $matches[1];
        } else {
            throw new RuntimeException("No class found in file: {$filePath}");
        }

        $fullClass = $namespace ? $namespace . '\\' . $className : $className;

        if (! class_exists($fullClass)) {
            require_once $filePath;
        }

        if (! class_exists($fullClass)) {
            throw new RuntimeException("Seeder class {$fullClass} could not be loaded from {$filePath}");
        }

        return $fullClass;
    }

    /**
     * Files that are versioned: sorted by the configured order, without the excluded seeders.
     *
     * @param SplFileInfo[] $files
     * @return SplFileInfo[]
     */
    protected function versionedFiles(array $files): array
    {
        return array_values(array_filter(
            $this->sortByConfiguredOrder($files),
            fn (SplFileInfo $file) => ! $this->isExcluded($file->getFilenameWithoutExtension())
        ));
    }

    /**
     * Seeders listed in the "order" config run first, in that order; the rest keep alphabetical order.
     *
     * @param SplFileInfo[] $files
     * @return SplFileInfo[]
     */
    protected function sortByConfiguredOrder(array $files): array
    {
        $position = array_flip(array_map('class_basename', config('seeder-versioning.order', [])));

        usort($files, fn (SplFileInfo $a, SplFileInfo $b) =>
            ($position[$a->getFilenameWithoutExtension()] ?? PHP_INT_MAX)
            <=> ($position[$b->getFilenameWithoutExtension()] ?? PHP_INT_MAX));

        return $files;
    }

    protected function recordHistory(string $seederName, string $hash, ?string $previousHash, bool $hashOnly): void
    {
        DB::table($this->historyTable)->insert([
            'seeder' => $seederName,
            'hash' => $hash,
            'previous_hash' => $previousHash,
            'hash_only' => $hashOnly,
            'ran_at' => now(),
        ]);
    }

    /**
     * Seeders are tracked by class name without namespace, so entries in the
     * except list are compared the same way.
     */
    public function isExcluded(string $seederName): bool
    {
        $except = array_map('class_basename', config('seeder-versioning.except', ['DatabaseSeeder']));

        return in_array($seederName, $except, true);
    }

    protected function calculateHash(string $path): string
    {
        $content = File::get($path);

        $normalized = preg_replace('/\s+/', ' ', $content);
        $normalized = trim($normalized);

        return hash('sha256', $normalized);
    }
}
