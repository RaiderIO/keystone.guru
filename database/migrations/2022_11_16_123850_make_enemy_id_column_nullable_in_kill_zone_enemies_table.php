<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeEnemyIdColumnNullableInKillZoneEnemiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kill_zone_enemies', function (Blueprint $table) {
            $table->integer('enemy_id')->default(null)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kill_zone_enemies', function (Blueprint $table) {
            $table->integer('enemy_id')->default(-1)->nullable(false)->change();
        });
    }
}
