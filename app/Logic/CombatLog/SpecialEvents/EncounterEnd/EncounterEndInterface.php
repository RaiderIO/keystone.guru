<?php

namespace App\Logic\CombatLog\SpecialEvents\EncounterEnd;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

interface EncounterEndInterface extends HasParameters
{
    public function getEncounterId(): int;

    public function getEncounterName(): string;

    public function getDifficultyId(): int;

    public function getGroupSize(): int;

    public function getSuccess(): int;

    public function getFightTimeMS(): int;
}
