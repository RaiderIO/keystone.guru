<?php

namespace App\Service\CombatLog\Models\ActivePull;

use App\Models\Enemy;
use Carbon\Carbon;

class ActivePullEnemy
{
    private string $uniqueId;

    private int $npcId;

    private float $x;

    private float $y;

    private Carbon $engagedAt;

    private ?Carbon $diedAt;

    private ?Enemy $resolvedEnemy;

    /**
     * @param string      $uniqueId
     * @param int         $npcId
     * @param float       $x
     * @param float       $y
     * @param Carbon      $engagedAt
     * @param Carbon|null $diedAt
     */
    public function __construct(string $uniqueId, int $npcId, float $x, float $y, Carbon $engagedAt, ?Carbon $diedAt)
    {
        $this->uniqueId      = $uniqueId;
        $this->npcId         = $npcId;
        $this->x             = $x;
        $this->y             = $y;
        $this->engagedAt     = $engagedAt;
        $this->diedAt        = $diedAt;
    }

    /**
     * @return string
     */
    public function getUniqueId(): string
    {
        return $this->uniqueId;
    }

    /**
     * @return int
     */
    public function getNpcId(): int
    {
        return $this->npcId;
    }

    /**
     * @return float
     */
    public function getX(): float
    {
        return $this->x;
    }

    /**
     * @return float
     */
    public function getY(): float
    {
        return $this->y;
    }

    /**
     * @return Carbon
     */
    public function getEngagedAt(): Carbon
    {
        return $this->engagedAt;
    }

    /**
     * @return Carbon
     */
    public function getDiedAt(): Carbon
    {
        return $this->diedAt;
    }

    /**
     * @return Enemy|null
     */
    public function getResolvedEnemy(): ?Enemy
    {
        return $this->resolvedEnemy;
    }

    /**
     * @param Enemy|null $resolvedEnemy
     * @return ActivePullEnemy
     */
    public function setResolvedEnemy(?Enemy $resolvedEnemy): self
    {
        $this->resolvedEnemy = $resolvedEnemy;

        return $this;
    }

    /**
     * @param Carbon $carbon
     * @return float
     */
    public function getHPPercentAt(Carbon $carbon): float
    {
        if ($this->getEngagedAt()->isAfter($carbon)) {
            return 100;
        }

        if ($this->getDiedAt()->isBefore($carbon)) {
            return 0;
        }

        $timeAliveMS  = $this->getEngagedAt()->diffInMilliseconds($this->getDiedAt());
        $snapshotAtMS = $this->getEngagedAt()->diffInMilliseconds($carbon);

        // timeAliveMS = 30000
        // snapShotAtMS = 15000
        // (30000 - 15000) / 30000 * 100

        return (($timeAliveMS - $snapshotAtMS) / $timeAliveMS) * 100;
    }
}
