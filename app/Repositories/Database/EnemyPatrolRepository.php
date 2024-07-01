<?php

namespace App\Repositories\Database;

use App\Models\EnemyPatrol;
use App\Repositories\Interfaces\EnemyPatrolRepositoryInterface;

class EnemyPatrolRepository extends DatabaseRepository implements EnemyPatrolRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(EnemyPatrol::class);
    }
}
