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
            $table->integer('raid_buffs_mask')->after('thundering_clear_seconds')->default(0);
            $table->index('raid_buffs_mask');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('simulation_craft_raid_events_options', function (Blueprint $table) {
            $table->dropColumn('raid_buffs_mask');
        });
    }
};
