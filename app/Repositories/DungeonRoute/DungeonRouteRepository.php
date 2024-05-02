<?php

namespace App\Repositories\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Repositories\BaseRepository;

class DungeonRouteRepository extends BaseRepository implements DungeonRouteRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRoute::class);
    }
}
