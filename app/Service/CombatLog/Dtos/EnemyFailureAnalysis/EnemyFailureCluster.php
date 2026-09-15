<?php

namespace App\Service\CombatLog\Dtos\EnemyFailureAnalysis;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use Carbon\Carbon;

/**
 * A group of enemy failures of one npc on one floor that happened close together, with everything needed to decide
 * what to do about the mapping there.
 */
readonly class EnemyFailureCluster
{
    /**
     * @param LatLng[] $hull The convex hull of the failures' map locations, empty when there are fewer than 3 distinct points
     */
    public function __construct(
        public int                 $npcId,
        public string              $npcName,
        public int                 $floorId,
        public int                 $count,
        public int                 $routeCount,
        public float               $avgFailuresPerRoute,
        public LatLng              $centroid,
        public IngameXY            $centroidIngameXY,
        public array               $hull,
        public ?Carbon             $firstSeen,
        public ?Carbon             $lastSeen,
        public ?int                $nearestEnemyId,
        public ?float              $nearestEnemyDistance,
        public ?int                $nearestEnemyFloorId,
        public ?int                $nearestEnemyPackGroup,
        public int                 $enemiesWithinRange,
        public EnemyFailureVerdict $verdict,
        public bool                $lowVolume,
        public string              $suggestion,
    ) {
    }
}
