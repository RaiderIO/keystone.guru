<?php

namespace App\Repositories\Database\DungeonRoute\Dtos;

class KillZoneEnemyForces
{
    public function __construct(
        public readonly int  $enemyForces,
        public readonly bool $hasBoss,
    ) {
    }
}
