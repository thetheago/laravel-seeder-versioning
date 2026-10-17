<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(config('seeder-versioning.history_table', 'seeder_version_history'), function (Blueprint $table) {
            $table->id();
            $table->string('seeder')->index();
            $table->string('hash', 64);
            $table->string('previous_hash', 64)->nullable();
            $table->boolean('hash_only')->default(false);
            $table->timestamp('ran_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('seeder-versioning.history_table', 'seeder_version_history'));
    }
};
