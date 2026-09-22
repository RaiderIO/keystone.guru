<?php

namespace App\Service\CombatLog\Dtos\EnemyResolutionAnalysis;

use App\Logic\Structs\LatLng;
use Carbon\Carbon;

/**
 * The long resolutions of one mapped pack on one floor (or of one enemy that is not in a pack), with everything needed
 * to decide whether the pack should move.
 */
readonly class EnemyResolutionGroup
{
    /**
     * @param int[]  $enemyIds             The enemies of the group that were resolved to from far away
     * @param string $npcNames             Comma separated, most resolved first
     * @param float  $routeShare           0..1 - of the routes that recorded any long resolution in this mapping version
     * @param float  $displacement         Ingame yards from the mapped centroid to the engaged centroid
     * @param float  $directionConsistency 0..1 - length of the mean unit offset vector
     * @param ?float $shapeRatio           Engaged spread divided by mapped spread of the members; null below 3 members
     */
    public function __construct(
        public int                    $floorId,
        public ?int                   $enemyPackId,
        public ?int                   $enemyPackGroup,
        public array                  $enemyIds,
        public string                 $npcNames,
        public int                    $count,
        public int                    $routeCount,
        public float                  $routeShare,
        public LatLng                 $engagedCentroid,
        public LatLng                 $mappedCentroid,
        public float                  $displacement,
        public float                  $directionConsistency,
        public ?float                 $shapeRatio,
        public ?Carbon                $firstSeen,
        public ?Carbon                $lastSeen,
        public EnemyResolutionVerdict $verdict,
        public bool                   $lowVolume,
        public string                 $suggestion,
    ) {
    }
}
