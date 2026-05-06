<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->boolean('speedrun_enabled')->after('timer_max_seconds')->default(0);

            $table->index('speedrun_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->dropColumn('speedrun_enabled');

            $table->dropIndex('dungeons_speedrun_enabled_index');
        });
    }
};
