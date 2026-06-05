<?php

namespace App\Service\CombatLog\Models\ActivePull;

use Illuminate\Support\Collection;

/**
 * @template TKey of array-key
 *
 * @template-covariant TValue
 *
 *  Ordered collection of all currently open ActivePulls; drives chain-pull detection and group-in-combat lookups.
 */
class ActivePullCollection extends Collection
{
    public function addNewPull(): ActivePull
    {
        $activePull = new ActivePull();
        $this->push($activePull);

        return $activePull;
    }

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
