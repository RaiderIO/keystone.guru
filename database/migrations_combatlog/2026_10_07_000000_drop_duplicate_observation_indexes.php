<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Each dropped index covers exactly the columns of the table's unique index, which every query plan picks instead.
     */
    public function up(): void
    {
        Schema::table('combat_log_spell_property_observations', function (Blueprint $table) {
            $table->dropIndex('clspo_spell_property_date_index');
        });

        Schema::table('combat_log_npc_characteristic_observations', function (Blueprint $table) {
            $table->dropIndex('clnco_npc_char_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('combat_log_spell_property_observations', function (Blueprint $table) {
            $table->index(['spell_id', 'property', 'observed_on'], 'clspo_spell_property_date_index');
        });

        Schema::table('combat_log_npc_characteristic_observations', function (Blueprint $table) {
            $table->index(['npc_id', 'characteristic_id', 'observed_on'], 'clnco_npc_char_date_index');
        });
    }
};
