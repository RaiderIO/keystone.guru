<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameModeColumnToDifficultyColumnInDungeonSpeedrunRequiredNpcsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dungeon_speedrun_required_npcs', function (Blueprint $table) {
            $table->renameColumn('mode', 'difficulty');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dungeon_speedrun_required_npcs', function (Blueprint $table) {
            $table->renameColumn('difficulty', 'mode');
        });
    }
}
