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
     * @param float $ingameDistance
     * @return float
     */
    public function calculateDelayForDistanceOnFoot(float $ingameDistance): float;

    /**
     * @param Enemy $enemy
     * @param int $enemyIndexInPull
     * @return self
     */
    public function addEnemy(Enemy $enemy, int $enemyIndexInPull): self;
}
