<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameDungeonSpeedrunRequiredNpcsModeColumnToDungeonDifficultyColumnInDungeonRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
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
    public function down()
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->renameColumn('dungeon_difficulty', 'dungeon_speedrun_required_npcs_mode');
        });
    }
}
