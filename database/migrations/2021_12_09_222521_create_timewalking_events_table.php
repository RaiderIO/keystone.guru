<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimewalkingEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('timewalking_events', function (Blueprint $table) {
            $table->id();
            $table->integer('expansion_id');
            $table->string('key');
            $table->string('name');
            $table->timestamp('start');
            $table->integer('start_duration_weeks');
            $table->integer('week_interval');

            $table->index('expansion_id');
            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('timewalking_events');
    }
}
