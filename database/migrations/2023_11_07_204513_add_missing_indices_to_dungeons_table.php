<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->index(['game_version_id']);
            $table->index(['zone_id']);
            $table->index(['map_id']);
            $table->index(['mdt_id']);
            $table->index(['key']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->dropIndex(['game_version_id']);
            $table->dropIndex(['zone_id']);
            $table->dropIndex(['map_id']);
            $table->dropIndex(['mdt_id']);
            $table->dropIndex(['key']);
        });
    }
};
