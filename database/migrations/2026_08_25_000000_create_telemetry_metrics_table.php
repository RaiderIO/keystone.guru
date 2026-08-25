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
        Schema::create('telemetry_metrics', function (Blueprint $table) {
            $table->bigIncrements('id');
            // Which series this row belongs to, e.g. 'scheduler', 'user_count', 'queue'.
            $table->string('measurement', 32);
            // The specific data point within the measurement: a command name for 'scheduler'
            // ('page-views:prune'), a gauge field otherwise ('all', 'discord', ...).
            $table->string('name', 64);
            // Optional secondary dimension, e.g. the Horizon queue name for the 'queue' measurement.
            $table->string('tag', 64)->nullable();
            // A double covers both ms durations and float gauges (percentages) with one column.
            $table->double('value');
            // Only meaningful for the 'scheduler' measurement: whether the command run succeeded.
            $table->boolean('success')->default(true);
            $table->timestamp('recorded_at')->useCurrent();

            $table->index(['measurement', 'name', 'recorded_at'], 'telemetry_metrics_meas_name_rec_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telemetry_metrics');
    }
};
