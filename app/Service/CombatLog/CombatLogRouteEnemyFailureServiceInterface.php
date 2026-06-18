<?php

namespace App\Service\CombatLog;

use App\Models\Dungeon;
use App\Service\CombatLog\Dtos\CombatLogRouteEnemyFailureHeatmapResult;

interface CombatLogRouteEnemyFailureServiceInterface
{
    /**
     * @param int[]|null $npcIds
     */
    public function getEnemyFailureHeatmapData(
        Dungeon $dungeon,
        ?array  $npcIds,
    ): CombatLogRouteEnemyFailureHeatmapResult;
}
