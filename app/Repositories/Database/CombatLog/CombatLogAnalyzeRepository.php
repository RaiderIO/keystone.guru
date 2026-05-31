<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\CombatLogAnalyze;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\CombatLogAnalyzeRepositoryInterface;

class CombatLogAnalyzeRepository extends DatabaseRepository implements CombatLogAnalyzeRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CombatLogAnalyze::class);
    }
}
