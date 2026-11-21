<?php

namespace App\Repositories\Interfaces\WalkGraph;

use App\Models\WalkGraph\FloorSwitchMarkerWalkDistance;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method FloorSwitchMarkerWalkDistance                  create(array<string, mixed> $attributes)
 * @method FloorSwitchMarkerWalkDistance|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method FloorSwitchMarkerWalkDistance                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method FloorSwitchMarkerWalkDistance                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                           save(FloorSwitchMarkerWalkDistance $model)
 * @method bool                                           update(FloorSwitchMarkerWalkDistance $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                           delete(FloorSwitchMarkerWalkDistance $model)
 * @method Collection<int, FloorSwitchMarkerWalkDistance> all()
 * @method bool                                           exists(array<string, mixed> $columns)
 */
interface FloorSwitchMarkerWalkDistanceRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Collection<int, FloorSwitchMarkerWalkDistance>
     */
    public function getByMappingVersionId(int $mappingVersionId): Collection;

    /**
     * Replaces every distance of the floor in the mapping version with the given rows.
     *
     * @param array<int, array{dungeon_floor_switch_marker_id: int, other_dungeon_floor_switch_marker_id: int, straight_distance: float, walk_distance: float|null}> $rows
     */
    public function replaceForFloor(int $mappingVersionId, int $floorId, array $rows): void;
}
