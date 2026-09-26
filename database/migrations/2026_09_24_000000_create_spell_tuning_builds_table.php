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
        Schema::create('spell_tuning_builds', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('game_version_id');
            // The pair of client builds spell:difftuning compared, recorded even when nothing changed.
            // `to_build_number` is the last segment of `to_build`, which is what history sorts on.
            $table->string('from_build', 32);
            $table->string('to_build', 32);
            $table->unsignedInteger('to_build_number');
            // When `to_build` went live on the CDN, in UTC, per wago.tools; null when it could not be looked up
            $table->dateTime('to_build_released_at')->nullable();

            $table->unique(['game_version_id', 'to_build'], 'spell_tuning_builds_gv_build_unique');
            $table->index(['game_version_id', 'to_build_number'], 'spell_tuning_builds_gv_number_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spell_tuning_builds');
    }
};
