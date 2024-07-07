<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('game_versions', function (Blueprint $table) {
            // Add active bool column
            $table->boolean('active')->default(true)->after('has_seasons');

            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_versions', function (Blueprint $table) {
            $table->dropColumn('active');
        });
    }
};
