<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSimulationCraftRaidEventsOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('simulation_craft_raid_events_options', function (Blueprint $table) {
            $table->id();
            $table->string('public_key');
            $table->integer('dungeon_route_id');
            $table->integer('user_id');
            $table->integer('key_level');
            $table->string('shrouded_bounty_type');
            $table->string('affix');
            $table->boolean('bloodlust');
            $table->boolean('arcane_intellect');
            $table->boolean('power_word_fortitude');
            $table->boolean('battle_shout');
            $table->boolean('mystic_touch');
            $table->boolean('chaos_brand');
            $table->float('hp_percent');
            $table->string('simulate_bloodlust_per_pull');
            $table->float('ranged_pull_compensation_yards');
            $table->boolean('use_mounts');
            $table->timestamps();

            $table->index('public_key');
            $table->index('dungeon_route_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('simulation_craft_raid_events_options');
    }
}
