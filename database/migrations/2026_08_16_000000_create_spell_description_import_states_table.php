<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Records the game build `wagotools:importspelldescriptions` last actually imported, per game
     * version - so a scheduled patch check can tell "wago.tools has a newer build" apart from "we
     * already imported this one". Purely additive, so backward-compatible with running code.
     */
    public function up(): void
    {
        Schema::create('spell_description_import_states', function (Blueprint $table) {
            $table->unsignedInteger('game_version_id')->primary();
            $table->string('product');
            $table->string('build');
            $table->dateTime('imported_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spell_description_import_states');
    }
};
