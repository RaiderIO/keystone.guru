<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\CombatLogEvent;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\CombatLogEventRepositoryInterface;

class CombatLogEventRepository extends DatabaseRepository implements CombatLogEventRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CombatLogEvent::class);
    }
}
