<?php

namespace App\Service\MDT\Import\Traits;

use App\Models\Enemy;
use Illuminate\Support\Collection;

trait FindsSavedEnemyFromCloneEnemy
{
    /**
     * @param Collection<int, Enemy> $savedEnemies
     */
    private function findSavedEnemyFromCloneEnemy(Collection $savedEnemies, int $npcId, int $mdtId): Enemy
    {
        return $savedEnemies->firstOrFail(static fn(
            Enemy $enemy,
        ) => $enemy->npc_id === $npcId && $enemy->mdt_id === $mdtId);
    }
}
