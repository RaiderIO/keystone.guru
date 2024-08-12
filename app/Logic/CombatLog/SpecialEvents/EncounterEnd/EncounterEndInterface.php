<?php

namespace App\Logic\CombatLog\SpecialEvents\EncounterEnd;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

interface EncounterEndInterface extends HasParameters
{
    public function getSuccess(): int;

    public function getFightTimeMS(): int;
}
