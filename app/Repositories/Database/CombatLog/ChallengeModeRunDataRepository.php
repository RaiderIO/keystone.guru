<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\ChallengeModeRunData;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\ChallengeModeRunDataRepositoryInterface;

class ChallengeModeRunDataRepository extends DatabaseRepository implements ChallengeModeRunDataRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(ChallengeModeRunData::class);
    }
}
