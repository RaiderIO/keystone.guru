<?php


namespace App\Service\LiveSession;

use App\Models\LiveSession;
use Illuminate\Support\Collection;

class DungeonRouteCorrection
{
    /** @var Collection */
    private Collection $obsoleteEnemies;

    /** @var int */
    private int $enemyForces;

    /**
     * RouteCorrection constructor.
     */
    public function __construct(LiveSession $liveSession)
    {
        $this->obsoleteEnemies = collect();
        $this->enemyForces     = $liveSession->dungeonroute->enemy_forces;
    }

    /**
     * @return Collection
     */
    public function getObsoleteEnemies(): Collection
    {
        return $this->obsoleteEnemies;
    }

    /**
     * @param int $enemyId
     */
    public function addObsoleteEnemy(int $enemyId)
    {
        $this->obsoleteEnemies->push($enemyId);
    }

    /**
     * @param Collection $enemies
     */
    public function addObsoleteEnemies(Collection $enemies)
    {
        $this->obsoleteEnemies = $this->obsoleteEnemies->merge($enemies);
    }

    /**
     * @return int
     */
    public function getEnemyForces(): int
    {
        return $this->enemyForces;
    }


    /**
     * @param int $enemyForces
     * @return DungeonRouteCorrection
     */
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
