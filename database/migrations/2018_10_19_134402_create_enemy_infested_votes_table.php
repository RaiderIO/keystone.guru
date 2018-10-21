<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEnemyInfestedVotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('enemy_infested_votes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('enemy_id');
            $table->boolean('vote');

            $table->index('user_id');
            $table->index('enemy_id');
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
        Schema::dropIfExists('infested_enemy_votes');
    }
}
