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
        return $cloneIndex + $this->getDungeonCloneIndexOffset($dungeon, $npcIndex);
    }

    /**
     * The inverse of applyDungeonCloneIndexHack(): turns an enemy's mdt_id back into the clone index MDT uses.
     */
    private function revertDungeonCloneIndexHack(Dungeon $dungeon, int $npcIndex, int $mdtId): int
    {
        return $mdtId - $this->getDungeonCloneIndexOffset($dungeon, $npcIndex);
    }

    private function getDungeonCloneIndexOffset(Dungeon $dungeon, int $npcIndex): int
    {
        if ($dungeon->key === DungeonKey::TOL_DAGOR->value && $npcIndex === 11) {
            return 2;
        }

        if ($dungeon->key === DungeonKey::MISTS_OF_TIRNA_SCITHE->value && $npcIndex === 23) {
            return 5;
        }

        return 0;
    }
}
