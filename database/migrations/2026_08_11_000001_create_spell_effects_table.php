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
        Schema::create('spell_effects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('spell_id');
            $table->unsignedTinyInteger('effect_index');
            $table->unsignedSmallInteger('effect_type');
            $table->unsignedSmallInteger('aura_type');
            // The game stores creature damage and healing as a coefficient, not an amount: the amount is
            // this multiplied by a constant that depends on the content the caster belongs to. Keeping the
            // coefficient is what lets the amount be recalculated later, per key level.
            $table->double('base_points');
            $table->double('variance');
            $table->unsignedInteger('period_ms');
            $table->unsignedSmallInteger('chain_targets');
            $table->double('radius')->nullable();
            $table->double('max_radius')->nullable();

            $table->unique(['spell_id', 'effect_index'], 'spell_effects_spell_effect_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spell_effects');
    }
};
