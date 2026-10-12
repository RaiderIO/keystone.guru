<?php

namespace App\Service\LiveSession;

use App\Models\LiveSession\LiveSession;
use Illuminate\Support\Collection;

class DungeonRouteCorrection
{
    /** @var Collection<int, int> */
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

    /**
     * @return Collection<int, int>
     */
    public function getObsoleteEnemies(): Collection
    {
        return $this->obsoleteEnemies;
    }

    public function addObsoleteEnemy(int $enemyId): void
    {
        $this->obsoleteEnemies->push($enemyId);
    }

    /**
     * @param Collection<int, int> $enemies
     */
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

    /**
     * @return array<string, Collection<int, int>|int>
     */
    public function toArray(): array
    {
        return [
            'obsolete_enemy_ids' => $this->obsoleteEnemies,
            'enemy_forces'       => $this->enemyForces,
        ];
    }
}
