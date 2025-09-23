<?php

namespace App\Service\CombatLog\Models\ActivePull;

use App\Logic\Structs\IngameXY;
use App\Models\Enemy;
use Illuminate\Support\Carbon;

class ActivePullEnemy
{
    private ?Enemy $resolvedEnemy = null;

    public function __construct(
        private readonly string  $uniqueId,
        private readonly int     $npcId,
        private readonly float   $x,
        private readonly float   $y,
        private readonly Carbon  $engagedAt,
        private readonly ?Carbon $diedAt,
    ) {
    }

    public function getUniqueId(): string
    {
        return $this->uniqueId;
    }

    public function getNpcId(): int
    {
        return $this->npcId;
    }

    public function getX(): float
    {
        return $this->x;
    }

    public function getY(): float
    {
        return $this->y;
    }

    public function getIngameXY(): IngameXY
    {
        return new IngameXY($this->x, $this->y);
    }

    public function getEngagedAt(): Carbon
    {
        return $this->engagedAt;
    }

    public function getDiedAt(): ?Carbon
    {
        return $this->diedAt;
    }

    public function getResolvedEnemy(): ?Enemy
    {
        return $this->resolvedEnemy;
    }

    public function setResolvedEnemy(?Enemy $resolvedEnemy): self
    {
        $this->resolvedEnemy = $resolvedEnemy;

        return $this;
    }

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
