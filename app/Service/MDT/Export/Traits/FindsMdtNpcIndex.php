<?php

namespace App\Service\MDT\Export\Traits;

use App\Models\Enemy;
use Illuminate\Support\Collection;

trait FindsMdtNpcIndex
{
    /**
     * Finds the MDT npc index of the MDT clone matching an npc_id/mdt_id pair, or -1 when MDT has no such clone.
     * MDTDungeon numbers clones per floor, so the same pair can exist on two floors under different npc indices -
     * a clone on $floorId wins over one on another floor.
     *
     * @param Collection<int, Enemy> $mdtEnemies
     */
    private function findMdtNpcIndex(Collection $mdtEnemies, int $npcId, ?int $mdtId, ?int $floorId): int
    {
        $mdtNpcIndex = -1;
        if ($mdtId === null) {
            return $mdtNpcIndex;
        }

        foreach ($mdtEnemies as $mdtEnemyCandidate) {
            if ($mdtEnemyCandidate->npc_id !== $npcId || $mdtEnemyCandidate->mdt_id !== $mdtId) {
                continue;
            }

            if ($floorId === null || $mdtEnemyCandidate->floor_id === $floorId) {
                return $mdtEnemyCandidate->mdt_npc_index;
            }

            if ($mdtNpcIndex === -1) {
                $mdtNpcIndex = $mdtEnemyCandidate->mdt_npc_index;
            }
        }

        return $mdtNpcIndex;
    }
}
