<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeEnemyForcesDefaultOnNpcs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Not installing Doctrine for eloquent way of doing this
        DB::statement('ALTER TABLE npcs ALTER COLUMN enemy_forces SET DEFAULT -1;');
        DB::table('npcs')->where('enemy_forces', '=', 0)->update(['enemy_forces' => -1]);
        // Bosses give 0 enemy forces and that's intended
        DB::table('npcs')->where('classification_id', '>', 3)->update(['enemy_forces' => 0]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Cannot reverse the classification_id statement so don't do that

        DB::statement('ALTER TABLE npcs ALTER COLUMN enemy_forces SET DEFAULT 0;');
        DB::table('npcs')->where('enemy_forces', '=', -1)->update(['enemy_forces' => 0]);
    }
}
