<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\EnemyPosition;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\EnemyPositionRepositoryInterface;

class EnemyPositionRepository extends DatabaseRepository implements EnemyPositionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(EnemyPosition::class);
    }
}
