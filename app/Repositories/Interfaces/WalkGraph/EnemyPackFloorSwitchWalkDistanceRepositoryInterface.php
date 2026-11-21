<?php

namespace App\Repositories\Interfaces\WalkGraph;

use App\Models\WalkGraph\EnemyPackFloorSwitchWalkDistance;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method EnemyPackFloorSwitchWalkDistance                  create(array<string, mixed> $attributes)
 * @method EnemyPackFloorSwitchWalkDistance|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method EnemyPackFloorSwitchWalkDistance                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method EnemyPackFloorSwitchWalkDistance                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                              save(EnemyPackFloorSwitchWalkDistance $model)
 * @method bool                                              update(EnemyPackFloorSwitchWalkDistance $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                              delete(EnemyPackFloorSwitchWalkDistance $model)
 * @method Collection<int, EnemyPackFloorSwitchWalkDistance> all()
 * @method bool                                              exists(array<string, mixed> $columns)
 */
interface EnemyPackFloorSwitchWalkDistanceRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Collection<int, EnemyPackFloorSwitchWalkDistance>
     */
    public function getByMappingVersionId(int $mappingVersionId): Collection;

    /**
     * Replaces every distance of the floor in the mapping version with the given rows.
     *
     * @param array<int, array{enemy_pack_id: int, dungeon_floor_switch_marker_id: int, straight_distance: float, walk_distance: float|null}> $rows
     */
    public function replaceForFloor(int $mappingVersionId, int $floorId, array $rows): void;
}
