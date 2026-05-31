<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\CombatLogParsingCriterion;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\CombatLogParsingCriterionRepositoryInterface;

class CombatLogParsingCriterionRepository extends DatabaseRepository implements CombatLogParsingCriterionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CombatLogParsingCriterion::class);
    }
}
