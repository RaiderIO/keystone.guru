<?php

namespace App\Logic\SimulationCraft;

use App\Logic\Structs\LatLng;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;

interface RaidEventPullInterface
{
    /**
     * @param LatLng[]      $path             Ordered waypoints from the previous kill zone (or dungeon start) to this kill zone.
     * @param KillZone|null $previousKillZone The preceding pull, or null when this pull is walked to from the dungeon start.
     *
     * @return $this
     */
    public function calculateRaidEventPullEnemies(KillZone $killZone, array $path, ?KillZone $previousKillZone = null): self;

    /**
     * @param LatLng[]      $path             Ordered waypoints representing the travel path to this pull.
     * @param KillZone|null $previousKillZone The preceding pull. When set, the group starts the path part of the
     *                                        way along it, having drifted towards this pull while fighting, and the
     *                                        fixed overhead between pulls is added.
     */
    public function calculateDelay(array $path, ?KillZone $previousKillZone = null): float;

    /**
     * @return float The share of the path to the next pull that the group covers while still fighting $previousKillZone.
     */
    public function getDriftFraction(KillZone $previousKillZone): float;

    /**
     * @param LatLng[] $path Ordered waypoints representing the travel path to this pull.
     *
     * @return list<LatLng> The path, starting $yards along it instead of at its first waypoint.
     */
    public function trimPathStart(array $path, float $yards): array;

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

    /**
     * @param bool $affixExempt Scale the enemy's health by the key level only, without Fortified or Tyrannical
     */
    public function addEnemy(Enemy $enemy, int $enemyIndexInPull, bool $affixExempt = false): self;

    /**
     * Whether any enemy is left to kill once the pull's enemies are calculated.
     */
    public function hasEnemies(): bool;

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
