<?php

namespace App\Logic\SimulationCraft;

use App\Logic\Structs\LatLng;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;

interface RaidEventPullInterface
{
    /**
     * @param LatLng[] $path Ordered waypoints from the previous kill zone (or dungeon start) to this kill zone.
     *
     * @return $this
     */
    public function calculateRaidEventPullEnemies(KillZone $killZone, array $path): self;

    /**
     * @param LatLng[] $path Ordered waypoints representing the travel path to this pull.
     */
    public function calculateDelay(array $path): float;

    public function calculateDelayBetweenPoints(LatLng $latLngA, LatLng $latLngB, bool $applyRangedCompensation = true): float;

    public function calculateMountedFactorAndMountCastsBetweenPoints(
        LatLng $latLngA,
        LatLng $latLngB,
    ): array;

    public function calculateDelayForDistanceMounted(float $ingameDistance, float $factor, int $speed): float;

    public function calculateDelayForDistanceOnFoot(float $ingameDistance): float;

    public function calculateDelayForMountCasts(int $mountCasts): float;

    public function addEnemy(Enemy $enemy, int $enemyIndexInPull): self;
}
