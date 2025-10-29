<?php

namespace Thetheago\SeederVersioning;

use Illuminate\Support\ServiceProvider;
use Thetheago\SeederVersioning\Command\SeederMigrateCommand;
use Thetheago\SeederVersioning\Services\SeederRunner;
use Thetheago\SeederVersioning\Services\SeederVersioningService;

class SeederVersioningServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/seeder-versioning.php', 'seeder-versioning');
        $this->app->singleton('seeder-versioning', SeederVersioningService::class);

        $this->app->singleton(SeederVersioningService::class, function ($app) {
            return new SeederVersioningService(new SeederRunner());
        });
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->registerCommands();
            $this->registerPublishes();
        }
    }

    /**
     * Register the package commands.
     */
    protected function registerCommands(): void
    {
        $this->commands([
            SeederMigrateCommand::class,
        ]);
    }

    /**
     * Register the publishable resources.
     */
    protected function registerPublishes(): void
    {
        $this->publishes([
            __DIR__ . '/../config/seeder-versioning.php' => config_path('seeder-versioning.php'),
            __DIR__ . '/../database/migrations/2025_01_01_000000_create_seeder_versions_table.php' =>
                database_path('migrations/' . date('Y_m_d_His') . '_create_seeder_versions_table.php'),
        ], 'seeder-versioning');
    }
}
