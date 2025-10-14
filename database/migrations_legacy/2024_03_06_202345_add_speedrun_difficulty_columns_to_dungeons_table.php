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
            $table->boolean('speedrun_difficulty_25_man_enabled')->after('speedrun_enabled');
            $table->boolean('speedrun_difficulty_10_man_enabled')->after('speedrun_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->dropColumn('speedrun_difficulty_10_man_enabled');
            $table->dropColumn('speedrun_difficulty_25_man_enabled');
        });
    }
};
