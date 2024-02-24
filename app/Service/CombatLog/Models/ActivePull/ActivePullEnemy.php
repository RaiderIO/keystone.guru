<?php

namespace App\Service\CombatLog\Models\ActivePull;

use App\Logic\Structs\IngameXY;
use App\Models\Enemy;
use Carbon\Carbon;

class ActivePullEnemy
{
    private ?Enemy $resolvedEnemy = null;

    /**
     * @param Carbon|null $diedAt
     */
    public function __construct(private string $uniqueId, private int $npcId, private float $x, private float $y, private Carbon $engagedAt, private ?Carbon $diedAt)
    {
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
     * @return IngameXY
     */
    public function getIngameXY(): IngameXY
    {
        return new IngameXY($this->x, $this->y);
    }

    /**
     * @return Carbon
     */
    public function getEngagedAt(): Carbon
    {
        return $this->engagedAt;
    }

    /**
     * @return Carbon|null
     */
    public function getDiedAt(): ?Carbon
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
     * @return float
     */
    public function getHPPercentAt(Carbon $carbon): float
    {
        // If it didn't die yet, we can't know the health based on engaged -> died
        if ($this->getEngagedAt()->isAfter($carbon) || $this->getDiedAt() === null) {
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
