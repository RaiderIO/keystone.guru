<?php

namespace App\Service\CombatLog\Models;

use App\Models\Enemy;

class ClosestEnemy
{
    /**
     * @var float Init to a high number to make the initial check always be closer than this, and it'll get
     * initialized with a real value
     */
    private float $distanceBetweenEnemies = 99999999;

    private float $distanceBetweenLastPullAndEnemy = 99999999;

    private float $weightedTotalDistance = 99999999;

    private ?Enemy $enemy = null;

    /**
     * @return float
     */
    public function getDistanceBetweenEnemies(): float
    {
        return $this->distanceBetweenEnemies;
    }

    /**
     * @param float $distanceBetweenEnemies
     *
     * @return ClosestEnemy
     */
    public function setDistanceBetweenEnemies(float $distanceBetweenEnemies): ClosestEnemy
    {
        $this->distanceBetweenEnemies = $distanceBetweenEnemies;
        return $this;
    }

    /**
     * @return float
     */
    public function getDistanceBetweenLastPullAndEnemy(): float
    {
        return $this->distanceBetweenLastPullAndEnemy;
    }

    /**
     * @param float $distanceBetweenLastPullAndEnemy
     *
     * @return ClosestEnemy
     */
    public function setDistanceBetweenLastPullAndEnemy(float $distanceBetweenLastPullAndEnemy): ClosestEnemy
    {
        $this->distanceBetweenLastPullAndEnemy = $distanceBetweenLastPullAndEnemy;
        return $this;
    }

    /**
     * @return float
     */
    public function getWeightedTotalDistance(): float
    {
        return $this->weightedTotalDistance;
    }

    /**
     * @param float $weightedTotalDistance
     *
     * @return ClosestEnemy
     */
    public function setWeightedTotalDistance(float $weightedTotalDistance): ClosestEnemy
    {
        $this->weightedTotalDistance = $weightedTotalDistance;
        return $this;
    }

    /**
     * @return Enemy|null
     */
    public function getEnemy(): ?Enemy
    {
        return $this->enemy;
    }

    /**
     * @param Enemy|null $enemy
     *
     * @return ClosestEnemy
     */
    public function setEnemy(?Enemy $enemy): ClosestEnemy
    {
        $this->enemy = $enemy;
        return $this;
    }
}
