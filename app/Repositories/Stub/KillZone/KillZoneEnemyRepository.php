<?php

namespace App\Repositories\Stub\KillZone;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\KillZone\KillZoneEnemy;
use App\Repositories\Interfaces\KillZone\KillZoneEnemyRepositoryInterface;
use App\Repositories\Stub\StubRepository;
use Illuminate\Support\Collection;
use stdClass;

class KillZoneEnemyRepository extends StubRepository implements KillZoneEnemyRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(KillZoneEnemy::class);
    }

    /**
     * @param Collection<int, int> $killZoneIds
     */
    public function resetEnemyIdByKillZoneIds(Collection $killZoneIds): void
    {
    }

    public function updateEnemyIdsByMappingVersion(int $dungeonRouteId, int $mappingVersionId): void
    {
    }

    /**
     * @param Collection<int, int> $killZoneIds
     */
    public function deleteOrphanedByKillZoneIds(Collection $killZoneIds): void
    {
    }

    /**
     * @param  array<int, int>                  $dungeonRouteIds
     * @param  array<int, int>                  $npcIds
     * @return Collection<int, array<int, int>>
     */
    public function getNpcIdsInPullsPerDungeonRoute(array $dungeonRouteIds, array $npcIds): Collection
    {
        return collect();
    }

    /**
     * @param  array<int, int>           $enemyIds
     * @return Collection<int, stdClass>
     */
    public function getPullIndicesOfEnemies(DungeonRoute $dungeonRoute, array $enemyIds, ?int $exceptKillZoneId = null): Collection
    {
        return collect();
    }
}
