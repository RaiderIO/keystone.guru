<?php

namespace App\Repositories\Database;

use App\Models\UserPinnedDungeonRouteCollection;
use App\Repositories\Interfaces\UserPinnedDungeonRouteCollectionRepositoryInterface;

class UserPinnedDungeonRouteCollectionRepository extends DatabaseRepository implements UserPinnedDungeonRouteCollectionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(UserPinnedDungeonRouteCollection::class);
    }
}
