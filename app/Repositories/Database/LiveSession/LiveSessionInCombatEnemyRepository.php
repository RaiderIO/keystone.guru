<?php

namespace App\Repositories\Database\LiveSession;

use App\Models\LiveSession\LiveSessionInCombatEnemy;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\LiveSession\LiveSessionInCombatEnemyRepositoryInterface;

class LiveSessionInCombatEnemyRepository extends DatabaseRepository implements LiveSessionInCombatEnemyRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(LiveSessionInCombatEnemy::class);
    }
}
