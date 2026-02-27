<?php

namespace App\Repositories\Database\DungeonRoute\Dtos;

use App\Models\DungeonRoute\DungeonRoute;

class SimilarDungeonRoute
{
    public function __construct(
        public readonly float        $similarity,
        public readonly DungeonRoute $dungeonRoute,
    ) {
    }
}
