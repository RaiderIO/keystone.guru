<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNullableToUserIdColumnInSimulationCraftRaidEventsOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('simulation_craft_raid_events_options', function (Blueprint $table) {
            $table->integer('user_id')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('simulation_craft_raid_events_options', function (Blueprint $table) {
            $table->integer('user_id')->nullable(false);
        });
    }
}
