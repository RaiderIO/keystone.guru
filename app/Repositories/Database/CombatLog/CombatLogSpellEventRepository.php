<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\CombatLogSpellEvent;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\CombatLogSpellEventRepositoryInterface;

class CombatLogSpellEventRepository extends DatabaseRepository implements CombatLogSpellEventRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CombatLogSpellEvent::class);
    }
}
