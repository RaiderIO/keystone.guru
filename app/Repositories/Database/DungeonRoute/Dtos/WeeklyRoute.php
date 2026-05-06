<?php

namespace App\Repositories\Database\DungeonRoute\Dtos;

use App\Models\DungeonRoute\DungeonRoute;

class WeeklyRoute
{
    public function __construct(
        public readonly string        $type,
        public readonly ?DungeonRoute $dungeonRoute,
    ) {
    }
}
