<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\Energize;

interface EnergizeInterface
{
    public function getAmount(): float;

    public function getOverEnergize(): float;

    public function getPowerType(): int;

    public function getMaxPower(): int;
}
