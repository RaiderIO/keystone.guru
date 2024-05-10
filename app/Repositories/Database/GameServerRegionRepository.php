<?php

namespace App\Repositories\Database;

use App\Models\GameServerRegion;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\GameServerRegionRepositoryInterface;

class GameServerRegionRepository extends DatabaseRepository implements GameServerRegionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(GameServerRegion::class);
    }
}
