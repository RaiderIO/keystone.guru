<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\CombatLogSpellPropertyObservation;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\CombatLogSpellPropertyObservationRepositoryInterface;

class CombatLogSpellPropertyObservationRepository extends DatabaseRepository implements CombatLogSpellPropertyObservationRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CombatLogSpellPropertyObservation::class);
    }
}
