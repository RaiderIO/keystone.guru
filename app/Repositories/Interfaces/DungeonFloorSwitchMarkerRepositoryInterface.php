<?php

namespace App\Repositories\Interfaces;

use App\Models\DungeonFloorSwitchMarker;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonFloorSwitchMarker                  create(array<string, mixed> $attributes)
 * @method DungeonFloorSwitchMarker|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonFloorSwitchMarker                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonFloorSwitchMarker                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                      save(DungeonFloorSwitchMarker $model)
 * @method bool                                      update(DungeonFloorSwitchMarker $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                      delete(DungeonFloorSwitchMarker $model)
 * @method Collection<int, DungeonFloorSwitchMarker> all()
 * @method bool                                      exists(array<int, string> $columns)
 */
interface DungeonFloorSwitchMarkerRepositoryInterface extends BaseRepositoryInterface
{
}
