<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\CombatLogRouteEnemyFailureRepositoryInterface;

class CombatLogRouteEnemyFailureRepository extends DatabaseRepository implements CombatLogRouteEnemyFailureRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CombatLogRouteEnemyFailure::class);
    }
}
