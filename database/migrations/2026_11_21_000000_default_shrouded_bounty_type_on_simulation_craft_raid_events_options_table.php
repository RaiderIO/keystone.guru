<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Setting a column default changes only the table's metadata (ALGORITHM=INSTANT), so this does not rebuild the
     * table.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `simulation_craft_raid_events_options` ALTER COLUMN `shrouded_bounty_type` SET DEFAULT 'none', ALGORITHM=INSTANT");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE `simulation_craft_raid_events_options` ALTER COLUMN `shrouded_bounty_type` DROP DEFAULT, ALGORITHM=INSTANT');
    }
};
