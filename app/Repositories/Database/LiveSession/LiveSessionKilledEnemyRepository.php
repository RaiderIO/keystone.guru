<?php

namespace App\Repositories\Database\LiveSession;

use App\Models\LiveSession\LiveSessionKilledEnemy;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\LiveSession\LiveSessionKilledEnemyRepositoryInterface;

class LiveSessionKilledEnemyRepository extends DatabaseRepository implements LiveSessionKilledEnemyRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(LiveSessionKilledEnemy::class);
    }
}
