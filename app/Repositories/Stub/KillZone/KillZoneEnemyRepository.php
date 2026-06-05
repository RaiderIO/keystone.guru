<?php

namespace App\Repositories\Stub\KillZone;

use App\Models\KillZone\KillZoneEnemy;
use App\Repositories\Interfaces\KillZone\KillZoneEnemyRepositoryInterface;
use App\Repositories\Stub\StubRepository;
use Illuminate\Support\Collection;

class KillZoneEnemyRepository extends StubRepository implements KillZoneEnemyRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(KillZoneEnemy::class);
    }

    /** @param Collection<int> $killZoneIds */
    public function resetEnemyIdByKillZoneIds(Collection $killZoneIds): void
    {
    }

    public function updateEnemyIdsByMappingVersion(int $dungeonRouteId, int $mappingVersionId): void
    {
    }

    /** @param Collection<int> $killZoneIds */
    public function deleteOrphanedByKillZoneIds(Collection $killZoneIds): void
    {
    }
}
