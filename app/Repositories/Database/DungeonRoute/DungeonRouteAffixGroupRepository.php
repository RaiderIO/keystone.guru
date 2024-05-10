<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteAffixGroupRepositoryInterface;

class DungeonRouteAffixGroupRepository extends DatabaseRepository implements DungeonRouteAffixGroupRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRouteAffixGroup::class);
    }
}
