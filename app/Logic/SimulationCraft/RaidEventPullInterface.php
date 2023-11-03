<?php

namespace App\Logic\SimulationCraft;

use App\Logic\Structs\LatLng;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\KillZone\KillZone;

interface RaidEventPullInterface
{
    /**
     * @param KillZone $killZone
     * @param LatLng   $previousKillLocation
     * @return $this
     */
    public function calculateRaidEventPullEnemies(KillZone $killZone, LatLng $previousKillLocation): self;

    /**
     * @param KillZone $killZone
     * @param LatLng   $previousKillLocation
     * @return float
     */
    public function calculateDelay(KillZone $killZone, LatLng $previousKillLocation): float;

    /**
     * @param LatLng $latLngA
     * @param LatLng $latLngB
     * @return float
     */
    public function calculateDelayBetweenPoints(LatLng $latLngA, LatLng $latLngB): float;

    /**
     * @param LatLng $latLngA
     * @param LatLng $latLngB
     * @return float
     */
    public function calculateDelayBetweenPointsOnDifferentFloors(LatLng $latLngA, LatLng $latLngB): float;

    /**
     * @param LatLng $latLngA
     * @param LatLng $latLngB
     * @return array
     */
    public function calculateMountedFactorAndMountCastsBetweenPoints(
        LatLng $latLngA, LatLng $latLngB
    ): array;

    /**
     * @param float $ingameDistance
     * @param float $factor
     * @param int   $speed
     * @return float
     */
    public function calculateDelayForDistanceMounted(float $ingameDistance, float $factor, int $speed): float;

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
     * @param int   $enemyIndexInPull
     * @return self
     */
    public function addEnemy(Enemy $enemy, int $enemyIndexInPull): self;
}
