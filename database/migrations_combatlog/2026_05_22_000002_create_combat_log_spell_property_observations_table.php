<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('combat_log_spell_property_observations', function (Blueprint $table) {
            $table->id();
            $table->integer('spell_id');
            // One of: aura, debuff, miss_absorb, miss_block, miss_deflect, miss_dodge,
            //         miss_evade, miss_immune, miss_miss, miss_parry, miss_reflect, miss_resist
            $table->string('property');
            $table->date('observed_on');
            $table->string('combat_log_path');
            $table->timestamps();

            $table->unique(['spell_id', 'property', 'observed_on'], 'clspo_spell_property_date_unique');
            $table->index(['spell_id', 'property', 'observed_on'], 'clspo_spell_property_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combat_log_spell_property_observations');
    }
};
