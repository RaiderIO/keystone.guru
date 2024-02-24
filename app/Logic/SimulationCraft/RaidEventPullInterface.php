<?php

namespace App\Logic\SimulationCraft;

use App\Logic\Structs\LatLng;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\KillZone\KillZone;

interface RaidEventPullInterface
{
    /**
     * @return $this
     */
    public function calculateRaidEventPullEnemies(KillZone $killZone, LatLng $previousKillLocation): self;

    /**
     * @return float
     */
    public function calculateDelay(KillZone $killZone, LatLng $previousKillLocation): float;

    /**
     * @return float
     */
    public function calculateDelayBetweenPoints(LatLng $latLngA, LatLng $latLngB): float;

    /**
     * @return float
     */
    public function calculateDelayBetweenPointsOnDifferentFloors(LatLng $latLngA, LatLng $latLngB): float;

    /**
     * @return array
     */
    public function calculateMountedFactorAndMountCastsBetweenPoints(
        LatLng $latLngA, LatLng $latLngB
    ): array;

    /**
     * @return float
     */
    public function calculateDelayForDistanceMounted(float $ingameDistance, float $factor, int $speed): float;

    /**
     * @return float
     */
    public function calculateDelayForDistanceOnFoot(float $ingameDistance): float;

    /**
     * @return float
     */
    public function calculateDelayForMountCasts(int $mountCasts): float;

    /**
     * @return self
     */
    public function addEnemy(Enemy $enemy, int $enemyIndexInPull): self;
}
