<?php

namespace App\Repositories\Database\Speedrun;

use App\Models\Speedrun\DungeonSpeedrunRequiredNpcNpc;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Speedrun\DungeonSpeedrunRequiredNpcNpcRepositoryInterface;

class DungeonSpeedrunRequiredNpcNpcRepository extends DatabaseRepository implements DungeonSpeedrunRequiredNpcNpcRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonSpeedrunRequiredNpcNpc::class);
    }
}
