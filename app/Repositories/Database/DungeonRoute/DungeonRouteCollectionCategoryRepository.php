<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteCollectionCategoryRepositoryInterface;

class DungeonRouteCollectionCategoryRepository extends DatabaseRepository implements DungeonRouteCollectionCategoryRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRouteCollectionCategory::class);
    }
}
