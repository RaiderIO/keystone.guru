<?php

namespace App\Service\CombatLog\Models\ActivePull;

use App\Service\CombatLog\Models\CreateRoute\CreateRouteNpc;

class CreateRouteBodyActivePull extends ActivePull
{
    /**
     * @param CreateRouteNpc $npc
     * @return ActivePull
     */
    public function enemyEngaged(CreateRouteNpc $npc): ActivePull
    {
        return parent::activePullEnemyEngaged($this->createActivePullEnemy($npc));
    }

    /**
     * @param CreateRouteNpc $npc
     * @return ActivePull
     */
    public function enemyKilled(CreateRouteNpc $npc): ActivePull
    {
        return parent::activePullEnemyKilled($this->createActivePullEnemy($npc));
    }

    /**
     * @param CreateRouteNpc $npc
     * @return ActivePullEnemy
     */
    private function createActivePullEnemy(CreateRouteNpc $npc): ActivePullEnemy
    {
        return new ActivePullEnemy(
            $npc->getUniqueId(),
            $npc->npcId,
            $npc->coord->x,
            $npc->coord->y,
            $npc->getEngagedAt(),
            $npc->getDiedAt(),
            $npc->getResolvedEnemy()
        );
    }
}
