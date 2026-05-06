<?php

namespace App\Logic\SimulationCraft;

use App\Logic\Structs\LatLng;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;

interface RaidEventPullInterface
{
    /**
     * @return $this
     */
    public function calculateRaidEventPullEnemies(KillZone $killZone, LatLng $previousKillLocation): self;

    public function calculateDelay(KillZone $killZone, LatLng $previousKillLocation): float;

    public function calculateDelayBetweenPoints(LatLng $latLngA, LatLng $latLngB): float;

    public function calculateDelayBetweenPointsOnDifferentFloors(LatLng $latLngA, LatLng $latLngB): float;

    public function calculateMountedFactorAndMountCastsBetweenPoints(
        LatLng $latLngA,
        LatLng $latLngB,
    ): array;

    public function calculateDelayForDistanceMounted(float $ingameDistance, float $factor, int $speed): float;

    public function calculateDelayForDistanceOnFoot(float $ingameDistance): float;

    public function calculateDelayForMountCasts(int $mountCasts): float;

    public function addEnemy(Enemy $enemy, int $enemyIndexInPull): self;
}
