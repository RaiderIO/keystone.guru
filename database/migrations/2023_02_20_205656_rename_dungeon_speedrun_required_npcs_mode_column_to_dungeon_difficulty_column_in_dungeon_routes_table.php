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
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->renameColumn('dungeon_speedrun_required_npcs_mode', 'dungeon_difficulty');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->renameColumn('dungeon_difficulty', 'dungeon_speedrun_required_npcs_mode');
        });
    }
};
