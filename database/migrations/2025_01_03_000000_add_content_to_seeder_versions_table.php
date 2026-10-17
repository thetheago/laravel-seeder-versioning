<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table(config('seeder-versioning.table', 'seeder_versions'), function (Blueprint $table) {
            $table->longText('content')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table(config('seeder-versioning.table', 'seeder_versions'), function (Blueprint $table) {
            $table->dropColumn('content');
        });
    }
};
