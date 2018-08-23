<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameEnemyPackVerticesXYLatLng extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('enemy_pack_vertices', function (Blueprint $table) {
            DB::statement("ALTER TABLE `enemy_pack_vertices` CHANGE COLUMN `x` `lat` double(8,2);");
            DB::statement("ALTER TABLE `enemy_pack_vertices` CHANGE COLUMN `y` `lng` double(8,2);");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('enemy_pack_vertices', function (Blueprint $table) {
            DB::statement("ALTER TABLE `enemy_pack_vertices` CHANGE COLUMN `lat` `x` double(8,2);");
            DB::statement("ALTER TABLE `enemy_pack_vertices` CHANGE COLUMN `lng` `y` double(8,2);");
        });
    }
}
