<?php

namespace App\Service\CombatLog;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Service\CombatLog\Dtos\CombatLogRouteEnemyResolutionHeatmapResult;
use App\Service\CombatLog\Enums\EnemyResolutionHeatmapMetric;
use Illuminate\Support\Collection;

interface CombatLogRouteEnemyResolutionServiceInterface
{
    /**
     * @param int[]|null $npcIds
     */
    public function getResolutionHeatmapData(
        Dungeon                      $dungeon,
        MappingVersion               $mappingVersion,
        ?array                       $npcIds,
        EnemyResolutionHeatmapMetric $metric,
        ?float                       $minDistance = null,
    ): CombatLogRouteEnemyResolutionHeatmapResult;

    /**
     * @return Collection<int, int> npc_id => recorded resolution count, most first
     */
    public function getResolutionCountsPerNpc(Dungeon $dungeon, MappingVersion $mappingVersion): Collection;

    /**
     * @return Collection<int, int> mapping_version_id => recorded resolution count, for every mapping version of the dungeon
     */
    public function getResolutionCountsPerMappingVersion(Dungeon $dungeon): Collection;
}
