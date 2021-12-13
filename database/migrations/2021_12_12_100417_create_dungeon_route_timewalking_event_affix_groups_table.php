<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDungeonRouteTimewalkingEventAffixGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dungeon_route_timewalking_event_affix_groups', function (Blueprint $table) {
            $table->id();
            $table->integer('dungeon_route_id');
            $table->integer('timewalking_event_affix_group_id');

            $table->index('dungeon_route_id', 'drteag_dungeon_route_id_index');
            $table->index('timewalking_event_affix_group_id', 'drteag_timewalking_event_affix_group_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dungeon_route_timewalking_event_affix_groups');
    }
}
