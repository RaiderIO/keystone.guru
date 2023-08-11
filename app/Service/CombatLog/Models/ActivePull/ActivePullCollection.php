<?php

namespace App\Service\CombatLog\Models\ActivePull;

use Illuminate\Support\Collection;

class ActivePullCollection extends Collection
{
    /**
     * @return ActivePull
     */
    public function addNewPull(): ActivePull
    {
        $activePull = new ActivePull();
        $this->push($activePull);

        return $activePull;
    }

    /**
     * @return Collection
     */
    public function getInCombatGroups(): Collection
    {
        $result = collect();

        foreach ($this as $activePull) {
            foreach ($activePull->getEnemiesInCombat() as $enemyInCombat) {
                /** @var ActivePullEnemy $enemyInCombat */
                $resolvedEnemy = $enemyInCombat->getResolvedEnemy();
                if ($resolvedEnemy !== null && $resolvedEnemy->enemy_pack_id !== null) {
                    $result->put($resolvedEnemy->enemyPack->group, true);
                }
            }
        }

        return $result;
    }
}
