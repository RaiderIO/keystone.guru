<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::delete('
            DELETE FROM `dungeon_route_affix_groups` WHERE affix_group_id IN (104, 105)
            ');

        DB::delete('
            DELETE FROM `affix_group_ease_tiers` WHERE affix_group_id IN (104, 105)
            ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
