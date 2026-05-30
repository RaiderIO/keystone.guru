<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\CombatLogNpcEventRepositoryInterface;

class CombatLogNpcEventRepository extends DatabaseRepository implements CombatLogNpcEventRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CombatLogNpcEvent::class);
    }
}
