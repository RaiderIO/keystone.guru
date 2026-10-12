<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionRoute;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRouteCollectionRoute                  create(array<string, mixed> $attributes)
 * @method DungeonRouteCollectionRoute|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteCollectionRoute                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteCollectionRoute                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                         save(DungeonRouteCollectionRoute $model)
 * @method bool                                         update(DungeonRouteCollectionRoute $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                         delete(DungeonRouteCollectionRoute $model)
 * @method Collection<int, DungeonRouteCollectionRoute> all()
 * @method bool                                         exists(array<int, string> $columns)
 */
interface DungeonRouteCollectionRouteRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * The ids of the routes the collection holds.
     *
     * @return Collection<int, int>
     */
    public function getDungeonRouteIds(DungeonRouteCollection $dungeonRouteCollection): Collection;

    /**
     * The highest order of the collection's routes, 0 for an empty collection.
     */
    public function getMaxOrder(DungeonRouteCollection $dungeonRouteCollection): int;

    /**
     * Removes the given routes from the collection.
     *
     * @param Collection<int, int> $dungeonRouteIds
     */
    public function deleteDungeonRoutes(DungeonRouteCollection $dungeonRouteCollection, Collection $dungeonRouteIds): int;

    public function updateOrder(DungeonRouteCollection $dungeonRouteCollection, int $dungeonRouteId, int $order): int;
}
