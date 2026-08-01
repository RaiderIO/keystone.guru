<?php

namespace App\Repositories\Database;

use App\Models\UserPinnedDungeonRoute;
use App\Repositories\Interfaces\UserPinnedDungeonRouteRepositoryInterface;

class UserPinnedDungeonRouteRepository extends DatabaseRepository implements UserPinnedDungeonRouteRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(UserPinnedDungeonRoute::class);
    }
}
