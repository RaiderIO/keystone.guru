<?php

namespace App\Service\CombatLog;

use App\Models\Dungeon;
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
     * worth 0 enemy forces in that mapping version are left out - see getZeroEnemyForcesNpcIds().
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
     * The ids of npcs that are explicitly worth 0 enemy forces in the mapping version. Failures for those npcs are noise
     * (they never affect a built route) and are excluded from every failure view; npcs without an enemy forces row at all
     * are NOT excluded, because "this npc is not in the mapping" is precisely what the failure triage should surface.
     *
     * @return int[]
     */
    public function getZeroEnemyForcesNpcIds(MappingVersion $mappingVersion): array;
}
