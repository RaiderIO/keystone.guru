<?php

use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Models\Season;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $deletedCount = DungeonRouteAffixGroup
            ::join('dungeon_routes', 'dungeon_routes.id', 'dungeon_route_affix_groups.dungeon_route_id')
            ->where('dungeon_routes.season_id', Season::SEASON_TWW_S1)
            ->where('dungeon_route_affix_groups.affix_group_id', '>=', 143)
            ->delete();

        info(sprintf('Deleted %s affix groups from dungeon routes that no longer exist.', $deletedCount));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ehhh too bad. We're fixing routes that are left without affix groups in the next migration.
    }
};
