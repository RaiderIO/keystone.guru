<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRoutePlayerSpecialization;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Database\Traits\ReplacesDungeonRouteRows;
use App\Repositories\Interfaces\DungeonRoute\DungeonRoutePlayerSpecializationRepositoryInterface;

class DungeonRoutePlayerSpecializationRepository extends DatabaseRepository implements DungeonRoutePlayerSpecializationRepositoryInterface
{
    use ReplacesDungeonRouteRows;

    public function __construct()
    {
        parent::__construct(DungeonRoutePlayerSpecialization::class);
    }

    /**
     * @param array<int, int|string> $ids
     */
    public function replaceForDungeonRoute(DungeonRoute $dungeonRoute, array $ids): void
    {
        $this->replaceDungeonRouteRows($dungeonRoute, 'character_class_specialization_id', $ids);
    }
}
