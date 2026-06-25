<?php

namespace App\Repositories\Interfaces\KillZone;

use App\Models\KillZone\KillZoneEnemy;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method KillZoneEnemy                  create(array<string, mixed> $attributes)
 * @method KillZoneEnemy|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method KillZoneEnemy                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method KillZoneEnemy                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                           save(KillZoneEnemy $model)
 * @method bool                           update(KillZoneEnemy $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                           delete(KillZoneEnemy $model)
 * @method Collection<int, KillZoneEnemy> all()
 * @method bool                           exists(array<int, string> $columns)
 */
interface KillZoneEnemyRepositoryInterface extends BaseRepositoryInterface
{
    /** @param Collection<int, int> $killZoneIds */
    public function resetEnemyIdByKillZoneIds(Collection $killZoneIds): void;

    public function updateEnemyIdsByMappingVersion(int $dungeonRouteId, int $mappingVersionId): void;

    /** @param Collection<int, int> $killZoneIds */
    public function deleteOrphanedByKillZoneIds(Collection $killZoneIds): void;
}
