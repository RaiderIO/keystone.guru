<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePolylinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('polylines', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('dungeon_route_id');
            $table->integer('floor_id');
            // route, patrol, brushline, line, etc.
            $table->string('type');
            $table->string('color');
            $table->integer('weight')->default(3);
            // JSON text chosen because we don't need the normalization of per-vertex. We just throw away all indices
            // and insert new ones anyways.
            $table->text('vertices_json');
            $table->timestamps();

            $table->index('floor_id');
            $table->index('dungeon_route_id');
            $table->index(['floor_id', 'dungeon_route_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('polylines');
    }
}
