<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFloorUnionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('floor_unions', function (Blueprint $table) {
            $table->id();
            $table->integer('floor_id');
            $table->integer('target_floor_id');
            $table->float('lat');
            $table->float('lng');
            $table->float('size');
            $table->float('rotation');

            $table->index(['floor_id', 'target_floor_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('floor_unions');
    }
}
