<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteCollectionRepositoryInterface;

class DungeonRouteCollectionRepository extends DatabaseRepository implements DungeonRouteCollectionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRouteCollection::class);
    }

    public function lockForUpdate(DungeonRouteCollection $dungeonRouteCollection): void
    {
        DungeonRouteCollection::query()->whereKey($dungeonRouteCollection->id)->lockForUpdate()->first();
    }

    public function touchUpdatedAt(DungeonRouteCollection $dungeonRouteCollection): void
    {
        DungeonRouteCollection::query()->whereKey($dungeonRouteCollection->id)->update(['updated_at' => now()]);
    }
}
