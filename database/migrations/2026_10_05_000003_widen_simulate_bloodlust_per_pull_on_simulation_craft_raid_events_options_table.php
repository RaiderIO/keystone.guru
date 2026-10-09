<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * The column holds a comma separated list of kill zone ids, one per pull up to the route's pull limit: 50 ids of
     * up to 10 digits do not fit in 255 characters. Both lengths need a two-byte length prefix, so widening the
     * VARCHAR changes only the table's metadata.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE `simulation_craft_raid_events_options` MODIFY `simulate_bloodlust_per_pull` VARCHAR(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('UPDATE `simulation_craft_raid_events_options` SET `simulate_bloodlust_per_pull` = LEFT(`simulate_bloodlust_per_pull`, 255) WHERE CHAR_LENGTH(`simulate_bloodlust_per_pull`) > 255');
        DB::statement('ALTER TABLE `simulation_craft_raid_events_options` MODIFY `simulate_bloodlust_per_pull` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
    }
};
