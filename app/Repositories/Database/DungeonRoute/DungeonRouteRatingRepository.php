<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteRating;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRatingRepositoryInterface;

class DungeonRouteRatingRepository extends DatabaseRepository implements DungeonRouteRatingRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRouteRating::class);
    }
}
