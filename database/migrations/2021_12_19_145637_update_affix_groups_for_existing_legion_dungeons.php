<?php

use App\Models\DungeonRoute;
use App\Models\DungeonRouteAffixGroup;
use App\Models\Expansion;
use App\Service\Expansion\ExpansionServiceInterface;
use Illuminate\Database\Migrations\Migration;

class UpdateAffixGroupsForExistingLegionDungeons extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $expansionService = app(ExpansionServiceInterface::class);
        $legionExpansion  = Expansion::where('shortname', Expansion::EXPANSION_LEGION)->first();
        if ($legionExpansion !== null) {
            $season = $expansionService->getCurrentSeason($legionExpansion);

            $result = DungeonRoute::select('dungeon_routes.*')
                ->where('dungeons.expansion_id', $legionExpansion->id)
                ->join('dungeons', 'dungeons.id', 'dungeon_routes.dungeon_id')
                ->get();

            foreach ($result as $dungeonRoute) {
                DungeonRouteAffixGroup::where('dungeon_route_id', $dungeonRoute->id)->delete();

                // Give the dungeon route new affix groups
                DungeonRouteAffixGroup::create([
                    'dungeon_route_id' => $dungeonRoute->id,
                    'affix_group_id'   => $season->affixgroups->first()->id // first affix in Legion
                ]);
            }
        } else {
            logger()->info('Unable to migrate existing affix groups for current Legion dungeons - assuming this is a fresh install and no routes can be updated anyways');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Nope
    }
}
