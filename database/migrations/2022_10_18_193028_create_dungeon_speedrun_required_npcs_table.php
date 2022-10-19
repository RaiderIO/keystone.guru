<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDungeonSpeedrunRequiredNpcsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dungeon_speedrun_required_npcs', function (Blueprint $table) {
            $table->id();
            $table->integer('dungeon_id');
            $table->integer('npc_id');
            $table->integer('count');

            $table->index(['dungeon_id', 'npc_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dungeon_speedrun_required_npcs');
    }
}
