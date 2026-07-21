<?php

namespace App\Repositories\Database\LiveSession;

use App\Models\LiveSession\LiveSessionObsoleteEnemy;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\LiveSession\LiveSessionObsoleteEnemyRepositoryInterface;

class LiveSessionObsoleteEnemyRepository extends DatabaseRepository implements LiveSessionObsoleteEnemyRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(LiveSessionObsoleteEnemy::class);
    }
}
