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
    protected string $table;
    protected SeederRunner $seederRunner;
    protected ConsoleOutput $output;

    public function __construct(SeederRunner $seederRunner)
    {
        $this->table = config('seeder-versioning.table', 'seeder_versions');
        $this->seederRunner = $seederRunner;
        $this->output = new ConsoleOutput();
    }

    /**
     * @throws RuntimeException
     */
    public function ensureSetup(): void
    {
        $configPath = config_path('seeder-versioning.php');

        if (! File::exists($configPath)) {
            throw new RuntimeException(
                "Config file not found {$configPath}. " .
                "Run: php artisan vendor:publish --tag=seeder-versioning"
            );
        }

        if (! Schema::hasTable('seeder_versions')) {
            $this->output->writeln("<fg=yellow>[SeederVersioning] </>Creating table <fg=cyan>{$this->table}</>...");
            $this->runSeederVersionMigration();
        }
    }

    public function runSeederVersionMigration(): void
    {
        $migrationFile = __DIR__ . '/../../database/migrations/2025_01_01_000000_create_seeder_versions_table.php';

        Artisan::call('migrate', [
            '--path' => $migrationFile,
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

        foreach ($files as $file) {
            $seeder = $this->resolveSeederClass($file);
            $seederName = $file->getFilenameWithoutExtension();
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

                $this->output->writeln("<fg=green> ✔ Seeder updated with new hash:</> <fg=cyan>{$hash}</>");
            }
        }

        if (! $anySeederRunned && ! $hashOnly) {
            $this->output->writeln("<fg=yellow>[SeederVersioning] </>No new or modified seeders to execute.");
        }
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

    protected function calculateHash(string $path): string
    {
        $content = File::get($path);

        $normalized = preg_replace('/\s+/', ' ', $content);
        $normalized = trim($normalized);

        return hash('sha256', $normalized);
    }
}
