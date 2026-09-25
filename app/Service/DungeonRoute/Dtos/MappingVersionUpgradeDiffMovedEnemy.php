<?php

namespace App\Service\DungeonRoute\Dtos;

use App\Models\Floor\Floor;
use App\Models\Npc\Npc;

/**
 * A pull enemy the upgrade kept, but which the new mapping version places somewhere else. The pull still
 * works; the path drawn towards it may now miss.
 */
readonly class MappingVersionUpgradeDiffMovedEnemy
{
    public function __construct(
        public MappingVersionUpgradeDiffPull $pull,
        public ?int                          $npcId,
        public ?Npc                          $npc,
        public int                           $enemyId,
        public ?Floor                        $oldFloor,
        public ?Floor                        $newFloor,
        public ?float                        $lat,
        public ?float                        $lng,
        /** How far the enemy moved, in ingame yards. Null when it changed floor, where a distance means nothing. */
        public ?float                        $distance,
    ) {
    }

    public function hasChangedFloor(): bool
    {
        return $this->distance === null;
    }
}
