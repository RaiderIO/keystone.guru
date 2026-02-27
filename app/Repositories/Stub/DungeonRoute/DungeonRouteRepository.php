<?php

namespace App\Repositories\Stub\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Repositories\Interfaces\DungeonRoute\Dtos\DungeonRouteSearchFilter;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Stub\StubRepository;
use Illuminate\Support\Collection;

class DungeonRouteRepository extends StubRepository implements DungeonRouteRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRoute::class);
    }

    public function generateRandomPublicKey(): string
    {
        // Just do something that is mostly unique
        return md5(uniqid());
    }

    public function getDungeonRoutesWithExpiredThumbnails(?Collection $dungeonRoutes = null): Collection
    {
        return $dungeonRoutes ?? collect();
    }

    public function getWeeklyRoutes(?Dungeon $dungeon = null): Collection
    {
        return collect();
    }

    public function findSimilarRoutes(DungeonRoute $dungeonRoute, int $limit = 5): Collection
    {
        return collect();
    }

    public function findRoutes(DungeonRouteSearchFilter $filter): Collection
    {
        return collect();
    }
}
