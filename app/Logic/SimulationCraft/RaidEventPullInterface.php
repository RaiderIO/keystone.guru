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
     * @param Enemy $enemy
     * @param int $enemyIndexInPull
     * @return self
     */
    public function addEnemy(Enemy $enemy, int $enemyIndexInPull): self;

    /**
     * @param float $ingameDistance
     * @return float
     */
    public function calculateDelayForDistance(float $ingameDistance): float;
}
