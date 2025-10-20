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
            $this->commands([
                SeederMigrateCommand::class,
            ]);
        }
    }
}
