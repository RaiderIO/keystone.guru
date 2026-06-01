<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\CombatLogNpcCharacteristicObservation;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\CombatLogNpcCharacteristicObservationRepositoryInterface;

class CombatLogNpcCharacteristicObservationRepository extends DatabaseRepository implements CombatLogNpcCharacteristicObservationRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CombatLogNpcCharacteristicObservation::class);
    }
}
