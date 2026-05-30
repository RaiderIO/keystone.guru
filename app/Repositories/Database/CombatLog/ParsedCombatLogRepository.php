<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\ParsedCombatLog;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\ParsedCombatLogRepositoryInterface;

class ParsedCombatLogRepository extends DatabaseRepository implements ParsedCombatLogRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(ParsedCombatLog::class);
    }
}
