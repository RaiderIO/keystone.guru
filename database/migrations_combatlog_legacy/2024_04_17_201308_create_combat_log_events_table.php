<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('combat_log_events', function (Blueprint $table) {
            $table->id();
            $table->string('run_id');
            $table->integer('challenge_mode_id');
            $table->integer('level');
            $table->string('affix_ids');
            $table->boolean('success');
            $table->timestamp('start');
            $table->timestamp('end');
            $table->integer('duration_ms');
            $table->integer('ui_map_id');
            $table->double('pos_x');
            $table->double('pos_y');
            $table->string('event_type');
            $table->json('characters');
            $table->json('context');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combat_log_events');
    }
};
