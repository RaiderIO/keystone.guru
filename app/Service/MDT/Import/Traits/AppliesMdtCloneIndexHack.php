<?php

namespace App\Service\MDT\Import\Traits;

use App\Models\Dungeon;

trait AppliesMdtCloneIndexHack
{
    /**
     * Hacky fix for an MDT bug where there's duplicate NPCs with the same npc_id etc: MDT lists
     * these NPCs twice under different mdt npc indices, whose clone index ranges collide unless
     * offset.
     */
    private function applyDungeonCloneIndexHack(Dungeon $dungeon, int $npcIndex, int $cloneIndex): int
    {
        if ($dungeon->key === Dungeon::DUNGEON_SIEGE_OF_BORALUS && $npcIndex === 35) {
            return $cloneIndex + 15;
        }

        if ($dungeon->key === Dungeon::DUNGEON_TOL_DAGOR && $npcIndex === 11) {
            return $cloneIndex + 2;
        }

        if ($dungeon->key === Dungeon::DUNGEON_MISTS_OF_TIRNA_SCITHE && $npcIndex === 23) {
            return $cloneIndex + 5;
        }

        return $cloneIndex;
    }
}
