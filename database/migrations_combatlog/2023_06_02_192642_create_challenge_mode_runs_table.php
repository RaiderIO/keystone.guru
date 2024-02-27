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
        Schema::create('challenge_mode_runs', function (Blueprint $table) {
            $table->id();
            $table->integer('dungeon_id');
            $table->integer('level');
            $table->boolean('success');
            $table->integer('total_time_ms');
            $table->timestamp('created_at');

            $table->index(['dungeon_id', 'level']);
            $table->index(['dungeon_id', 'total_time_ms']);
            $table->index(['dungeon_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('challenge_mode_runs');
    }
};
