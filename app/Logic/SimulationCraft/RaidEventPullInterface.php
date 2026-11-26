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

    /**
     * @param LatLng[] $stretch Consecutive points on one floor, walked in order.
     */
    public function calculateDelayAlongStretch(array $stretch, bool $applyRangedCompensation = true): float;

    /**
     * @param LatLng[] $stretch Consecutive points on one floor, walked in order.
     *
     * @return array{0: list<array{factor: float, speed: int}>, 1: int} The mounted factors and speeds, and the number of mount casts.
     */
    public function calculateMountedFactorAndMountCastsAlongStretch(array $stretch): array;

    public function calculateDelayForDistanceMounted(float $ingameDistance, float $factor, int $speed): float;

    public function calculateDelayForDistanceOnFoot(float $ingameDistance): float;

    public function calculateDelayForMountCasts(int $mountCasts): float;

    public function addEnemy(Enemy $enemy, int $enemyIndexInPull): self;

    public function getPullIndex(): int;

    /**
     * @return int The travel delay in seconds towards this pull, as written to the raid event.
     */
    public function getDelay(): int;

    /**
     * @return float The in-game yards walked towards this pull, before the ranged pull compensation.
     */
    public function getTravelYards(): float;

    /**
     * @param LatLng[] $path Ordered waypoints representing the travel path to this pull.
     *
     * @return float The in-game length of the path, leaving out the zero-length jumps between floors.
     */
    public function calculateTravelYards(array $path): float;
}
