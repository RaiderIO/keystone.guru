<?php

namespace App\Logic\SimulationCraft;

use App\Models\Enemy;
use App\Models\KillZone;

interface RaidEventPullInterface
{
    /**
     * @param KillZone $killZone
     * @param array $previousLocation
     * @return $this
     */
    public function calculateRaidEventPullEnemies(KillZone $killZone, array $previousLocation): self;

    /**
     * @param Enemy $enemy
     * @param int $enemyIndexInPull
     * @return self
     */
    public function addEnemy(Enemy $enemy, int $enemyIndexInPull): self;
}
