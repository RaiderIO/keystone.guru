<?php

namespace App\Service\CombatLog;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Mapping\MappingVersion;
use App\Service\CombatLog\Dtos\CombatLogRouteEnemyFailureHeatmapResult;
use Illuminate\Support\Collection;

interface CombatLogRouteEnemyFailureServiceInterface
{
    /**
     * @param int[]|null $npcIds
     */
    public function getEnemyFailureHeatmapData(
        Dungeon        $dungeon,
        MappingVersion $mappingVersion,
        ?array         $npcIds,
    ): CombatLogRouteEnemyFailureHeatmapResult;

    /**
     * How many failures each npc has for the dungeon in the given mapping version, most failures first. Npcs that are
     * not worth any enemy forces in that mapping version are left out - see getNonZeroEnemyForcesNpcIds().
     *
     * @return Collection<int, int> npc_id => failure count
     */
    public function getFailureCountsPerNpc(Dungeon $dungeon, MappingVersion $mappingVersion): Collection;

    /**
     * How many failures the dungeon has per mapping version (including mapping versions with 0 failures).
     *
     * @return Collection<int, int> mapping_version_id => failure count
     */
    public function getFailureCountsPerMappingVersion(Dungeon $dungeon): Collection;

    /**
     * How many failures each of the given dungeon routes has, npcs not worth any enemy forces in that route's mapping
     * version left out the same way every other failure view leaves them out.
     *
     * @param  Collection<int, DungeonRoute> $dungeonRoutes keyed by dungeon route id
     * @return Collection<int, int>          dungeon_route_id => failure count (routes without failures are absent)
     */
    public function getFailureCountsPerDungeonRoute(Collection $dungeonRoutes): Collection;

    /**
     * The ids of npcs worth more than 0 enemy forces in the mapping version - the only npcs whose placement failures are
     * worth triaging, since no other npc can affect a built route's enemy forces. An npc without an enemy forces row at
     * all counts as 0 here: that is how "worth no enemy forces" is normally stored, an explicit 0 row being rare.
     *
     * An empty result means the mapping version has no enemy forces tuned at all, and callers must then fall back to not
     * filtering rather than hiding everything.
     *
     * @return int[]
     */
    public function getNonZeroEnemyForcesNpcIds(MappingVersion $mappingVersion): array;
}
