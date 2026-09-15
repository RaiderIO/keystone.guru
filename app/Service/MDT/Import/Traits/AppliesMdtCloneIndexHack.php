<?php

namespace App\Service\MDT\Import\Traits;

use App\Models\Dungeon;
use App\Models\DungeonKey;

trait AppliesMdtCloneIndexHack
{
    /**
     * Hacky fix for an MDT bug where there's duplicate NPCs with the same npc_id etc: MDT lists
     * these NPCs twice under different mdt npc indices, whose clone index ranges collide unless
     * offset.
     */
    private function applyDungeonCloneIndexHack(Dungeon $dungeon, int $npcIndex, int $cloneIndex): int
    {
        if ($dungeon->key === DungeonKey::SIEGE_OF_BORALUS->value && $npcIndex === 35) {
            return $cloneIndex + 15;
        }

        if ($dungeon->key === DungeonKey::TOL_DAGOR->value && $npcIndex === 11) {
            return $cloneIndex + 2;
        }

        if ($dungeon->key === DungeonKey::MISTS_OF_TIRNA_SCITHE->value && $npcIndex === 23) {
            return $cloneIndex + 5;
        }

        return $cloneIndex;
    }
}
