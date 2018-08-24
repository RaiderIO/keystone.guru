<?php

use Illuminate\Database\Migrations\Migration;

class SetObjectReferenceDefaults extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE enemies ALTER COLUMN npc_id SET DEFAULT -1;');
        DB::statement('ALTER TABLE dungeon_floor_switch_markers ALTER COLUMN target_floor_id SET DEFAULT -1;');
        DB::statement('ALTER TABLE enemy_patrols ALTER COLUMN enemy_id SET DEFAULT -1;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // It's not really vital; can do without a downgrade tbh.
    }
}
