<?php

namespace App\Service\CombatLog;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Service\CombatLog\Dtos\EnemyResolutionAnalysis\CombatLogRouteEnemyResolutionAnalysisResult;

interface CombatLogRouteEnemyResolutionAnalysisServiceInterface
{
    /**
     * Groups the dungeon's recorded long resolutions of the mapping version per mapped pack and floor (an enemy without
     * a pack is its own group), and gives every group a verdict on whether the pack is mapped in the wrong place.
     *
     * @param int[]|null $npcIds      Limit to these npcs
     * @param float|null $minDistance Only resolutions at least this far off, on the weighted distance
     */
    public function analyze(
        Dungeon        $dungeon,
        MappingVersion $mappingVersion,
        ?array         $npcIds = null,
        ?float         $minDistance = null,
    ): CombatLogRouteEnemyResolutionAnalysisResult;
}
