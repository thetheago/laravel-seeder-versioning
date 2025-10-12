<?php

namespace Thetheago\SeederVersioning\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SeederVersioningService
{
    protected string $table;
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->table = config('seeder-versioning.table', 'seeder_versions');
        $this->app = $app;
    }

    public function runVersionedSeeders(): void
    {
        $path = config('seeder-versioning.path', database_path('seeders'));

        if (! File::exists($path)) {
            return;
        }

        $files = File::files($path);

        foreach ($files as $file) {
            if (! Str::endsWith($file->getFilename(), '.php')) {
                continue;
            }

            $seeder = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $hash = $this->calculateHash($file->getPathname());

            $existing = DB::table($this->table)->where('seeder', $seeder)->first();

            if (! $existing) {
                $this->runSeeder($seeder, $file->getPathname());
                DB::table($this->table)->insert([
                    'seeder' => $seeder,
                    'hash' => $hash,
                    'ran_at' => now(),
                ]);
                continue;
            }

            if ($existing->hash !== $hash) {
                $this->runSeeder($seeder, $file->getPathname());
                DB::table($this->table)->where('seeder', $seeder)->update([
                    'hash' => $hash,
                    'ran_at' => now(),
                ]);
            }
        }
    }

    protected function calculateHash(string $path): string
    {
        $content = File::get($path);

        $normalized = preg_replace('/\/\/.*|\/\*.*?\*\//s', '', $content);
        $normalized = preg_replace('/\s+/', '', $normalized);

        return hash('sha256', $normalized);
    }

    protected function runSeeder(string $class, string $path): void
    {
        if (! class_exists($class)) {
            require_once $path;
        }

        if (! class_exists($class)) {
            $fallBackPath = "Database\\Seeders\\{$class}";
            if (class_exists($fallBackPath)) {
                $class = $fallBackPath;
            }
        }

        $instance = app($class);
        if (is_object($instance) && method_exists($instance, 'run')) {
            $instance->run();
        }
    }
}
