<?php

namespace App\Repositories\Database\Enemies;

use App\Models\LiveSession\LiveSessionOverpulledEnemy;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Enemies\LiveSessionOverpulledEnemyRepositoryInterface;

class LiveSessionOverpulledEnemyRepository extends DatabaseRepository implements LiveSessionOverpulledEnemyRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(LiveSessionOverpulledEnemy::class);
    }
}
