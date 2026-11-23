<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAttribute;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Database\Traits\ReplacesDungeonRouteRows;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteAttributeRepositoryInterface;

class DungeonRouteAttributeRepository extends DatabaseRepository implements DungeonRouteAttributeRepositoryInterface
{
    use ReplacesDungeonRouteRows;

    public function __construct()
    {
        parent::__construct(DungeonRouteAttribute::class);
    }

    /**
     * @param array<int, int|string> $ids
     */
    public function replaceForDungeonRoute(DungeonRoute $dungeonRoute, array $ids): void
    {
        $this->replaceDungeonRouteRows($dungeonRoute, 'route_attribute_id', $ids);
    }
}
