<?php

namespace App\Service\CombatLog\Models\ActivePull;

use Carbon\Carbon;

class ResultEventActivePull extends ActivePull
{
    /**
     * @param Carbon $timestamp
     * @return float
     */
    public function getAverageHPPercentAt(Carbon $timestamp): float
    {
        // @TODO I need to know death times of all enemies in combat at this point, but I don't know it.
        // I cannot just consider $this->enemiesKilled since I want to consider all enemies currently engaged
        // en estimate their current HP, not those of dead enemies (it's always going to be 0 really).
        // So there needs to be some rewrites to make chainpulling work for this.
        return 100;
//        $inCombatSum = $this->enemiesInCombat->sum(function (EnemyEngaged $enemyEngaged) use ($timestamp) {
//
//            if ($enemyEngaged->getEngagedEvent()->getTimestamp()->isAfter($timestamp)) {
//                return 100;
//            }
//
//            if ($this->getDiedAt()->isBefore($carbon)) {
//                return 0;
//            }
//
//            $timeAliveMS = $this->getEngagedAt()->diffInMilliseconds($this->getDiedAt());
//            $snapshotAtMS = $this->getEngagedAt()->diffInMilliseconds($carbon);
//
//            // timeAliveMS = 30000
//            // snapShotAtMS = 15000
//            // (30000 - 15000) / 30000 * 100
//
//            return (($timeAliveMS - $snapshotAtMS) / $timeAliveMS) * 100;
//
//            return $enemyEngaged->getHPPercentAt($timestamp);
//        });
//
//        $totalEnemiesInPull = ($this->enemiesInCombat->count() + $this->enemiesKilled->count());
//        if ($totalEnemiesInPull === 0) {
//            return 100;
//        } else {
//            return $inCombatSum / $totalEnemiesInPull;
//        }
    }
}