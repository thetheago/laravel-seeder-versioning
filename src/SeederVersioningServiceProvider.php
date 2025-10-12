<?php

namespace Thetheago\SeederVersioning;

use Illuminate\Support\ServiceProvider;
use Thetheago\SeederVersioning\Services\SeederVersioningService;

class SeederVersioningServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/seeder-versioning.php', 'seeder-versioning');
        $this->app->singleton(Services\SeederVersioningService::class, SeederVersioningService::class);
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/seeder-versioning.php' => config_path('seeder-versioning.php'),
            ], 'config');

            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'migrations');
        }
    }
}
