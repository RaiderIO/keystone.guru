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
        Schema::create('spell_tuning_changes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('game_version_id');
            $table->unsignedBigInteger('spell_id');
            // The pair of client builds this change was observed between. `to_build_number` is the last
            // segment of `to_build`, which is what climbs between patches - it is what history sorts on,
            // since the build string itself does not sort (12.1.0.9999 < 12.1.0.69404 as text).
            $table->string('from_build', 32);
            $table->string('to_build', 32);
            $table->unsignedInteger('to_build_number');
            $table->string('change_type', 32);
            // For a value change: which number in the description moved (its position in
            // spells.description_values) and what kind of number it is. Null for a rewritten description.
            $table->unsignedTinyInteger('value_index')->nullable();
            $table->string('kind', 16)->nullable();
            // Damage/healing are coefficients, not amounts - the amount is coefficient / 10 times the
            // spell's damage multiplier, which cancels out of the delta. The rendered texts are kept as
            // they were at each build so the change can be shown in the numbers a player actually saw.
            $table->double('old_coefficient')->nullable();
            $table->double('new_coefficient')->nullable();
            $table->text('old_text')->nullable();
            $table->text('new_text')->nullable();
            // new / old - 1 for scalable values; null for everything else (and when old was 0).
            $table->double('delta')->nullable();

            $table->index(['spell_id', 'to_build_number'], 'spell_tuning_changes_spell_build_index');
            $table->index(['game_version_id', 'to_build_number'], 'spell_tuning_changes_gv_build_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spell_tuning_changes');
    }
};
