<?php

namespace Thetheago\SeederVersioning\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use SplFileInfo;

class SeederVersioningService
{
    protected string $table;
    protected Application $app;

    protected SeederRunner $seederRunner;

    public function __construct(Application $app, SeederRunner $seederRunner)
    {
        $this->table = config('seeder-versioning.table', 'seeder_versions');
        $this->app = $app;
        $this->seederRunner = $seederRunner;
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
                "Execute: php artisan vendor:publish --tag=seeder-versioning"
            );
        }

        if (! Schema::hasTable('seeder_versions')) {
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
    }

    /**
     * @param bool $hashOnly If true, just generate and fill the table without running the migrations
     */
    public function runVersionedSeeders(bool $hashOnly = false): void
    {
        $path = config('seeder-versioning.path', database_path('seeders'));

        if (!File::exists($path)) {
            return;
        }

        $files = File::files($path);

        foreach ($files as $file) {
            if (!Str::endsWith($file->getFilename(), '.php')) {
                continue;
            }

            $seeder = $this->resolveSeederClass($file);
            $seederName = $file->getFilenameWithoutExtension();
            $hash = $this->calculateHash($file->getPathname());

            $existing = DB::table($this->table)->where('seeder', $seederName)->first();

            if (!$existing) {
                if (!$hashOnly) {
                    $this->seederRunner::run($seeder);
                }

                DB::table($this->table)->insert([
                    'seeder' => $seederName,
                    'hash' => $hash,
                    'ran_at' => now(),
                ]);
                continue;
            }

            if ($existing->hash !== $hash) {
                if (!$hashOnly) {
                    $this->seederRunner::run($seeder);
                }

                DB::table($this->table)->where('seeder', $seederName)->update([
                    'hash' => $hash,
                    'ran_at' => now(),
                ]);
            }
        }
    }

    protected function resolveSeederClass(SplFileInfo $file): string
    {
        $fileName = $file->getFilename();

        if (class_exists($fileName)) {
            return $fileName;
        }

        $filePath = $file->getPathname();

        $contents = File::get($filePath);

        if (preg_match('/^namespace\s+(.+?);/m', $contents, $matches)) {
            $namespace = $matches[1];
        } else {
            $namespace = null;
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
