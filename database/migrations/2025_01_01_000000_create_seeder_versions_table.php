<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(config('seeder-versioning.table', 'seeder_versions'), function (Blueprint $table) {
            $table->id();
            $table->string('seeder')->unique();
            $table->string('hash', 64);
            $table->timestamp('ran_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('seeder-versioning.table', 'seeder_versions'));
    }
};
