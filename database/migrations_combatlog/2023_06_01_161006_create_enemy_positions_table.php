<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('enemy_positions', function (Blueprint $table) {
            $table->id();
            $table->string('guid');
            $table->integer('floor_id');
            $table->integer('npc_id');
            $table->float('lat');
            $table->float('lng');
            $table->timestamp('created_at');

            $table->index(['guid']);
            $table->index(['floor_id', 'npc_id']);
            $table->index(['lat', 'lng']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('enemy_positions');
    }
};
