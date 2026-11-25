<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRoutePlayerClass;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Database\Traits\ReplacesDungeonRouteRows;
use App\Repositories\Interfaces\DungeonRoute\DungeonRoutePlayerClassRepositoryInterface;

class DungeonRoutePlayerClassRepository extends DatabaseRepository implements DungeonRoutePlayerClassRepositoryInterface
{
    use ReplacesDungeonRouteRows;

    public function __construct()
    {
        parent::__construct(DungeonRoutePlayerClass::class);
    }

    /**
     * @param array<int, int|string> $ids
     */
    public function replaceForDungeonRoute(DungeonRoute $dungeonRoute, array $ids): void
    {
        $this->replaceDungeonRouteRows($dungeonRoute, 'character_class_id', $ids);
    }
}
