<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddModeColumnToDungeonSpeedrunRequiredNpcsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dungeon_speedrun_required_npcs', function (Blueprint $table) {
            $table->integer('mode')->after('npc5_id');

            $table->dropIndex(['floor_id']);
            $table->index(['floor_id', 'mode']);
        });

        DB::update('
            UPDATE `dungeon_speedrun_required_npcs` SET mode = :mode
            ', ['mode' => \App\Models\Speedrun\DungeonSpeedrunRequiredNpc::MODE_25_MAN]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dungeon_speedrun_required_npcs', function (Blueprint $table) {
            $table->dropColumn('mode');

            $table->dropIndex(['floor_id', 'mode']);
            $table->index(['floor_id']);
        });
    }
}
