<?php

namespace App\Service\LiveSession;

use App\Models\LiveSession;
use Illuminate\Support\Collection;

class DungeonRouteCorrection
{
    private Collection $obsoleteEnemies;

    private int $enemyForces;

    /**
     * RouteCorrection constructor.
     */
    public function __construct(LiveSession $liveSession)
    {
        $this->obsoleteEnemies = collect();
        $this->enemyForces     = $liveSession->dungeonRoute->enemy_forces;
    }

    public function getObsoleteEnemies(): Collection
    {
        return $this->obsoleteEnemies;
    }

    public function addObsoleteEnemy(int $enemyId): void
    {
        $this->obsoleteEnemies->push($enemyId);
    }

    public function addObsoleteEnemies(Collection $enemies): void
    {
        $this->obsoleteEnemies = $this->obsoleteEnemies->merge($enemies);
    }

    public function getEnemyForces(): int
    {
        return $this->enemyForces;
    }

    public function setEnemyForces(int $enemyForces): DungeonRouteCorrection
    {
        $this->enemyForces = $enemyForces;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'obsolete_enemy_ids' => $this->obsoleteEnemies,
            'enemy_forces'       => $this->enemyForces,
        ];
    }
}
