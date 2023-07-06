<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChallengeModeRunDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('challenge_mode_run_data', function (Blueprint $table) {
            $table->id();
            $table->integer('challenge_mode_run_id');
            $table->string('run_id');
            $table->string('correlation_id');
            $table->mediumText('post_body');

            $table->index(['challenge_mode_run_id']);
            $table->index(['run_id']);
            $table->index(['correlation_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('challenge_mode_run_data');
    }
}
