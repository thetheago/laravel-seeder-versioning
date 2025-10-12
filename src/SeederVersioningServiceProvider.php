<?php

namespace Thetheago\SeederVersioning;

use Illuminate\Support\ServiceProvider;

class SeederVersioningServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/seeder-versioning.php', 'seeder-versioning');
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/seeder-versioning.php' => config_path('seeder-versioning.php'),
        ], 'config');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
