<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteFavorite;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteFavoriteRepositoryInterface;

class DungeonRouteFavoriteRepository extends DatabaseRepository implements DungeonRouteFavoriteRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRouteFavorite::class);
    }
}
