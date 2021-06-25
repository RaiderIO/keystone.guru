<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLiveSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('live_sessions', function (Blueprint $table)
        {
            $table->id();
            $table->integer('dungeon_route_id');
            $table->integer('user_id');
            $table->string('public_key');
            $table->timestamps();

            $table->index(['dungeon_route_id']);
            $table->index(['user_id']);
            $table->index(['public_key']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('live_sessions');
    }
}
