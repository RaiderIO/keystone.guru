<?php

namespace App\Repositories\Stub\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteAffixGroupRepositoryInterface;
use App\Repositories\Stub\StubRepository;

class DungeonRouteAffixGroupRepository extends StubRepository implements DungeonRouteAffixGroupRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRouteAffixGroup::class);
    }
}
