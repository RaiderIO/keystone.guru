<?php

namespace App\Repositories\Database\Speedrun;

use App\Models\Speedrun\DungeonSpeedrunDifficulty;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Speedrun\DungeonSpeedrunDifficultyRepositoryInterface;

class DungeonSpeedrunDifficultyRepository extends DatabaseRepository implements DungeonSpeedrunDifficultyRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonSpeedrunDifficulty::class);
    }
}
