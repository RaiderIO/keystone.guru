<?php

namespace App\Repositories\Interfaces\WalkGraph;

use App\Models\WalkGraph\EnemyPackWalkDistance;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method EnemyPackWalkDistance                  create(array<string, mixed> $attributes)
 * @method EnemyPackWalkDistance|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method EnemyPackWalkDistance                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method EnemyPackWalkDistance                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                   save(EnemyPackWalkDistance $model)
 * @method bool                                   update(EnemyPackWalkDistance $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                   delete(EnemyPackWalkDistance $model)
 * @method Collection<int, EnemyPackWalkDistance> all()
 * @method bool                                   exists(array<string, mixed> $columns)
 */
interface EnemyPackWalkDistanceRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Collection<int, EnemyPackWalkDistance>
     */
    public function getByMappingVersionId(int $mappingVersionId): Collection;

    /**
     * Replaces every distance of the floor in the mapping version with the given rows.
     *
     * @param array<int, array{enemy_pack_id: int, other_enemy_pack_id: int, straight_distance: float, walk_distance: float|null}> $rows
     */
    public function replaceForFloor(int $mappingVersionId, int $floorId, array $rows): void;
}
