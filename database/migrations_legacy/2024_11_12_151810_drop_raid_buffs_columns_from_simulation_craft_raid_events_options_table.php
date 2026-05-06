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
        Schema::table('simulation_craft_raid_events_options', function (Blueprint $table) {
            $table->dropColumn('bloodlust');
            $table->dropColumn('arcane_intellect');
            $table->dropColumn('power_word_fortitude');
            $table->dropColumn('battle_shout');
            $table->dropColumn('mystic_touch');
            $table->dropColumn('chaos_brand');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('simulation_craft_raid_events_options', function (Blueprint $table) {
            $table->integer('chaos_brand')->default(0)->after('raid_buffs_mask');
            $table->integer('mystic_touch')->default(0)->after('raid_buffs_mask');
            $table->integer('battle_shout')->default(0)->after('raid_buffs_mask');
            $table->integer('power_word_fortitude')->default(0)->after('raid_buffs_mask');
            $table->integer('arcane_intellect')->default(0)->after('raid_buffs_mask');
            $table->integer('bloodlust')->default(0)->after('raid_buffs_mask');
        });
    }
};
