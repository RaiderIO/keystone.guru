<?php

namespace App\Logic\CombatLog\SpecialEvents\EncounterStart;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

interface EncounterStartInterface extends HasParameters
{
    public function getInstanceID(): int;
}
