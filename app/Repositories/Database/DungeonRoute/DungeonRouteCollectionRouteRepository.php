<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionRoute;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteCollectionRouteRepositoryInterface;
use Illuminate\Support\Collection;

class DungeonRouteCollectionRouteRepository extends DatabaseRepository implements DungeonRouteCollectionRouteRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRouteCollectionRoute::class);
    }

    /**
     * @return Collection<int, int>
     */
    public function getDungeonRouteIds(DungeonRouteCollection $dungeonRouteCollection): Collection
    {
        return DungeonRouteCollectionRoute::query()
            ->where('dungeon_route_collection_id', $dungeonRouteCollection->id)
            ->pluck('dungeon_route_id');
    }

    public function getMaxOrder(DungeonRouteCollection $dungeonRouteCollection): int
    {
        return (int)DungeonRouteCollectionRoute::query()
            ->where('dungeon_route_collection_id', $dungeonRouteCollection->id)
            ->max('order');
    }

    /**
     * @param Collection<int, int> $dungeonRouteIds
     */
    public function deleteDungeonRoutes(DungeonRouteCollection $dungeonRouteCollection, Collection $dungeonRouteIds): int
    {
        return DungeonRouteCollectionRoute::query()
            ->where('dungeon_route_collection_id', $dungeonRouteCollection->id)
            ->whereIn('dungeon_route_id', $dungeonRouteIds)
            ->delete();
    }

    public function updateOrder(DungeonRouteCollection $dungeonRouteCollection, int $dungeonRouteId, int $order): int
    {
        return DungeonRouteCollectionRoute::query()
            ->where('dungeon_route_collection_id', $dungeonRouteCollection->id)
            ->where('dungeon_route_id', $dungeonRouteId)
            ->update(['order' => $order]);
    }
}
