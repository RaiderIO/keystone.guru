<?php

namespace App\Logic\SimulationCraft;

use App\Models\Enemy;
use App\Models\Floor;
use App\Models\KillZone;

interface RaidEventPullInterface
{
    /**
     * @param KillZone $killZone
     * @param array $previousKillLocation
     * @param Floor $previousKillFloor
     * @return $this
     */
    public function calculateRaidEventPullEnemies(KillZone $killZone, array $previousKillLocation, Floor $previousKillFloor): self;

    /**
     * @param KillZone $killZone
     * @param array $previousKillLocation
     * @param Floor $previousKillFloor
     * @return float
     */
    public function calculateDelay(KillZone $killZone, array $previousKillLocation, Floor $previousKillFloor): float;

    /**
     * @param Floor $floor
     * @param array $pointA
     * @param array $pointB
     * @return float
     */
    public function calculateDelayBetweenPoints(Floor $floor, array $pointA, array $pointB): float;

    /**
     * @param Floor $pointAFloor
     * @param Floor $pointBFloor
     * @param array $pointA
     * @param array $pointB
     * @return float
     */
    public function calculateDelayBetweenPointsOnDifferentFloors(Floor $pointAFloor, Floor $pointBFloor, array $pointA, array $pointB): float;

    /**
     * @param Floor $floor
     * @param array $pointA
     * @param array $pointB
     * @return array
     */
    public function calculateMountedFactorAndMountCastsBetweenPoints(
        Floor $floor,
        array $pointA,
        array $pointB
    ): array;

    /**
     * @param float $ingameDistance
     * @return float
     */
    public function calculateDelayForDistanceMounted(float $ingameDistance): float;

    /**
     * @param float $ingameDistance
     * @return float
     */
    public function calculateDelayForDistanceOnFoot(float $ingameDistance): float;

    /**
     * @param int $mountCasts
     * @return float
     */
    public function calculateDelayForMountCasts(int $mountCasts): float;

    /**
     * @param Enemy $enemy
     * @param int $enemyIndexInPull
     * @return self
     */
    public function addEnemy(Enemy $enemy, int $enemyIndexInPull): self;
}
