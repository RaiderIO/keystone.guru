<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\ChallengeModeRun;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\ChallengeModeRunRepositoryInterface;

class ChallengeModeRunRepository extends DatabaseRepository implements ChallengeModeRunRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(ChallengeModeRun::class);
    }
}
