<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOverpulledEnemiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('overpulled_enemies', function (Blueprint $table) {
            $table->id();
            $table->integer('live_session_id');
            $table->integer('enemy_id');

            $table->index(['live_session_id', 'enemy_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('overpulled_enemies');
    }
}
