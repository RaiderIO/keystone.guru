<?php

namespace App\Service\DungeonRoute\Dtos;

use App\Models\Floor\Floor;
use App\Models\Npc\Npc;

/**
 * One enemy the upgrade dropped from the route, or one the new mapping version requires that the route
 * does not kill.
 */
readonly class MappingVersionUpgradeDiffEnemy
{
    public function __construct(
        /** The pull the enemy was in; null for an enemy the route never had. */
        public ?MappingVersionUpgradeDiffPull $pull,
        public ?int                           $npcId,
        public ?Npc                           $npc,
        /** The enemy this row points at - the old one for a removal, the new one for a required enemy. */
        public ?int                           $enemyId,
        public ?Floor                         $floor,
        public ?float                         $lat,
        public ?float                         $lng,
        /** Only meaningful on a required enemy: true when the old mapping version did not require it. */
        public bool                           $newlyRequired = false,
    ) {
    }
}
