<?php

namespace App\Repositories\Database\Npc;

use App\Models\Npc\NpcEnemyForces;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Npc\NpcEnemyForcesRepositoryInterface;

class NpcEnemyForcesRepository extends DatabaseRepository implements NpcEnemyForcesRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(NpcEnemyForces::class);
    }
}
