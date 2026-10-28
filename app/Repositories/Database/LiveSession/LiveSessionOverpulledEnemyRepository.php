<?php

namespace App\Repositories\Database\LiveSession;

use App\Models\LiveSession\LiveSessionOverpulledEnemy;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\LiveSession\LiveSessionOverpulledEnemyRepositoryInterface;

class LiveSessionOverpulledEnemyRepository extends DatabaseRepository implements LiveSessionOverpulledEnemyRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(LiveSessionOverpulledEnemy::class);
    }
}
