<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeEnemyPackIdColumnNullableInEnemiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('enemies', function (Blueprint $table) {
            $table->integer('enemy_pack_id')->nullable()->change();
        });

        DB::update('UPDATE `enemies` SET `enemy_pack_id` = null WHERE `enemy_pack_id` = -1');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('enemies', function (Blueprint $table) {
            $table->integer('enemy_pack_id')->nullable(false)->change();
        });
    }
}
