<?php

namespace App\Repositories\Database\Enemies;

use App\Models\Enemies\OverpulledEnemy;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Enemies\OverpulledEnemyRepositoryInterface;

class OverpulledEnemyRepository extends DatabaseRepository implements OverpulledEnemyRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(OverpulledEnemy::class);
    }
}
