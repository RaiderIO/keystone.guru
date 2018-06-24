<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEnemyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('enemy', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('enemy_pack_id');
            $table->integer('npc_id');
            $table->integer('floor_id');
            $table->double('lat');
            $table->double('lng');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('enemy');
    }
}
