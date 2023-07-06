<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFloorIdColumnToDungeonSpeedrunRequiredNpcsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dungeon_speedrun_required_npcs', function (Blueprint $table) {
            $table->dropColumn('dungeon_id');
            $table->integer('floor_id')->after('id');
            $table->index(['floor_id']);
            $table->dropIndex(['dungeon_id', 'npc_id']);
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
            $table->integer('dungeon_id')->after('id');
            $table->dropColumn('floor_id');
            $table->index(['dungeon_id', 'npc_id']);
            $table->dropIndex(['floor_id']);
        });
    }
}
