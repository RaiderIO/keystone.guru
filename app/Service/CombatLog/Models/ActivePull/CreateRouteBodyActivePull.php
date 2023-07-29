<?php

namespace App\Service\CombatLog\Models\ActivePull;

use App\Service\CombatLog\Models\CreateRoute\CreateRouteNpc;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * @method Collection|CreateRouteNpc[] getEnemiesInCombat()
 * @method Collection|CreateRouteNpc[] getEnemiesKilled()
 */
class CreateRouteBodyActivePull extends ActivePull
{
    /**
     * @param Carbon $timestamp
     * @return float
     */
    public function getAverageHPPercentAt(Carbon $timestamp): float
    {
        $inCombatSum = $this->enemiesInCombat->sum(function (CreateRouteNpc $createRouteNpc) use ($timestamp) {
            return $createRouteNpc->getHPPercentAt($timestamp);
        });

        $totalEnemiesInPull = ($this->enemiesInCombat->count() + $this->enemiesKilled->count());
        if ($totalEnemiesInPull === 0) {
            return 100;
        } else {
            return $inCombatSum / $totalEnemiesInPull;
        }
    }
}