<?php

namespace Thetheago\SeederVersioning\Services;

use Illuminate\Support\Facades\DB;

class SeederVersioningService
{
    public function getVersion(string $seeder): ?string
    {
        return DB::table(config('seeder-versioning.table'))
            ->where('seeder', $seeder)
            ->value('version');
    }

    public function setVersion(string $seeder, string $version): void
    {
        DB::table(config('seeder-versioning.table'))->updateOrInsert(
            ['seeder' => $seeder],
            ['version' => $version, 'updated_at' => now()]
        );
    }
}
