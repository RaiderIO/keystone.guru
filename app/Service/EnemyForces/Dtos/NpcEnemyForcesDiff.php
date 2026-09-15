<?php

namespace App\Service\EnemyForces\Dtos;

/** What the game client and we each award for killing one NPC. */
class NpcEnemyForcesDiff
{
    public function __construct(
        public readonly int     $npcId,
        public readonly ?string $npcName,
        public readonly ?int    $db2EnemyForces,
        public readonly ?int    $ourEnemyForces,
    ) {
    }

    public function matches(): bool
    {
        return $this->db2EnemyForces === $this->ourEnemyForces;
    }
}
