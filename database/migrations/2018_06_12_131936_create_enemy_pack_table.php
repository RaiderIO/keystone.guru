<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEnemyPackTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('enemy_packs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('floor_id');
            $table->string('label');
            $table->timestamps();
        });
        Schema::create('enemy_pack_vertices', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('enemy_pack_id');
            $table->float('x');
            $table->float('y');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('enemy_pack');
        Schema::dropIfExists('enemy_pack_vertices');
    }
}
