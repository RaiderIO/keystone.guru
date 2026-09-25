<?php

namespace App\Service\DungeonRoute\Dtos;

use App\Models\Npc\Npc;

/**
 * An NPC the route pulls whose enemy forces the new mapping version values differently.
 */
readonly class MappingVersionUpgradeDiffNpcEnemyForces
{
    public function __construct(
        public int  $npcId,
        public ?Npc $npc,
        public ?int $oldEnemyForces,
        public ?int $newEnemyForces,
    ) {
    }
}
