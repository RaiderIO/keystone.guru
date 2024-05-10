<?php

namespace App\Repositories\Database\Speedrun;

use App\Models\Speedrun\DungeonSpeedrunRequiredNpc;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Speedrun\DungeonSpeedrunRequiredNpcRepositoryInterface;

class DungeonSpeedrunRequiredNpcRepository extends DatabaseRepository implements DungeonSpeedrunRequiredNpcRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonSpeedrunRequiredNpc::class);
    }
}
