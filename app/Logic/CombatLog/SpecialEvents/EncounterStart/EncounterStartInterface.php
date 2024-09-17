<?php

namespace App\Logic\CombatLog\SpecialEvents\EncounterStart;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

interface EncounterStartInterface extends HasParameters
{
    public function getEncounterId(): int;

    public function getEncounterName(): string;

    public function getDifficultyId(): int;

    public function getGroupSize(): int;

    public function getInstanceID(): int;
}
