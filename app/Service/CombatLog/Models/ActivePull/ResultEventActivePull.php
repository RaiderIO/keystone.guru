<?php

namespace App\Service\CombatLog\Models\ActivePull;

use App\Service\CombatLog\ResultEvents\EnemyEngaged;

class ResultEventActivePull extends ActivePull
{
    /**
     * @param EnemyEngaged $enemyEngaged
     * @return ActivePull
     */
    public function enemyEngagedCreateRouteNpc(EnemyEngaged $enemyEngaged): ActivePull
    {
        return parent::activePullEnemyEngaged($this->createActivePullEnemy($enemyEngaged));
    }

    /**
     * @param EnemyEngaged $enemyEngaged
     * @return ActivePull
     */
    public function enemyKilledCreateRouteNpc(EnemyEngaged $enemyEngaged): ActivePull
    {
        return parent::activePullEnemyKilled($this->createActivePullEnemy($enemyEngaged));
    }

    /**
     * @param EnemyEngaged $enemyEngaged
     * @return ActivePullEnemy
     */
    private function createActivePullEnemy(EnemyEngaged $enemyEngaged): ActivePullEnemy
    {
        return new ActivePullEnemy(
            $enemyEngaged->getGuid()->getGuid(),
            $enemyEngaged->getGuid()->getId(),
            $enemyEngaged->getEngagedEvent()->getAdvancedData()->getPositionX(),
            $enemyEngaged->getEngagedEvent()->getAdvancedData()->getPositionY(),
            $enemyEngaged->getEngagedEvent()->getTimestamp(),
            // @TODO We don't know this yet!
            null,
            $enemyEngaged->getResolvedEnemy()
        );
    }
}
