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
        DB::statement("
            UPDATE mapping_versions mv
            INNER JOIN dungeons d ON d.id = mv.dungeon_id
            SET mv.game_version_id = d.game_version_id
            WHERE d.game_version_id IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mapping_versions', function (Blueprint $table) {
            //
        });
    }
};
