<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Health derived from a combat log (observed health scaled up from its percentage) or fetched from Wowhead can
     * exceed a signed int. Non-strict MySQL clamped such a value to 2147483647; strict mode rejects the write.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE `npc_healths` MODIFY `health` BIGINT NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('UPDATE `npc_healths` SET `health` = 2147483647 WHERE `health` > 2147483647');
        DB::statement('ALTER TABLE `npc_healths` MODIFY `health` INT NOT NULL');
    }
};
