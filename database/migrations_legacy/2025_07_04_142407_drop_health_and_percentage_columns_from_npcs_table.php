<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('npcs', function (Blueprint $table) {
            $table->dropColumn(['base_health', 'health_percentage']);
        });
    }

    public function down(): void
    {
        Schema::table('npcs', function (Blueprint $table) {
            $table->integer('base_health')->nullable();
            $table->integer('health_percentage')->nullable();
        });
    }
};
