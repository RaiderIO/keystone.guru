<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMapCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('map_comments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('floor_id');
            $table->integer('dungeon_route_id')->default(-1);
            $table->integer('game_icon_id');
            $table->double('lat');
            $table->double('lng');
            $table->boolean('always_visible');
            $table->text('comment');
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
        Schema::dropIfExists('map_comments');
    }
}
