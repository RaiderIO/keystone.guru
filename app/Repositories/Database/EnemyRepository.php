<?php

namespace App\Repositories\Database;

use App\Models\Enemy;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\EnemyRepositoryInterface;

class EnemyRepository extends DatabaseRepository implements EnemyRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Enemy::class);
    }
}
