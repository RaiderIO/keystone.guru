<?php

namespace App\Service\CombatLog;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Service\CombatLog\Dtos\EnemyFailureAnalysis\CombatLogRouteEnemyFailureAnalysisResult;

interface CombatLogRouteEnemyFailureAnalysisServiceInterface
{
    /**
     * Clusters the dungeon's enemy failures of the mapping version per npc and location, and gives every cluster a
     * verdict on what is most likely wrong with the mapping there. The "look here" list for a mapping review.
     *
     * @param int[]|null $npcIds   Limit to these npcs
     * @param int|null   $minCount Below this many failures a cluster is flagged low-volume (default from config)
     */
    public function analyze(
        Dungeon        $dungeon,
        MappingVersion $mappingVersion,
        ?array         $npcIds = null,
        ?int           $minCount = null,
    ): CombatLogRouteEnemyFailureAnalysisResult;
}
