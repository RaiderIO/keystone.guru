<?php

namespace App\Repositories\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Repositories\BaseRepository;

class DungeonRouteAffixGroupRepository extends BaseRepository implements DungeonRouteAffixGroupRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRouteAffixGroup::class);
    }
}
