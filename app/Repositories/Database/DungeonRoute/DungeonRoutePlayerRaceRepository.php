<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRoutePlayerRace;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Database\Traits\ReplacesDungeonRouteRows;
use App\Repositories\Interfaces\DungeonRoute\DungeonRoutePlayerRaceRepositoryInterface;

class DungeonRoutePlayerRaceRepository extends DatabaseRepository implements DungeonRoutePlayerRaceRepositoryInterface
{
    use ReplacesDungeonRouteRows;

    public function __construct()
    {
        parent::__construct(DungeonRoutePlayerRace::class);
    }

    /**
     * @param array<int, int|string> $ids
     */
    public function replaceForDungeonRoute(DungeonRoute $dungeonRoute, array $ids): void
    {
        $this->replaceDungeonRouteRows($dungeonRoute, 'character_race_id', $ids);
    }
}
