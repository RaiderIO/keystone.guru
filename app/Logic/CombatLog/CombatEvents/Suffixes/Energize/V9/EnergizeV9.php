<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\Energize\V9;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\CombatEvents\Suffixes\Energize\EnergizeInterface;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;

class EnergizeV9 extends Suffix implements EnergizeInterface
{
    private float $amount;

    private float $overEnergize;

    private int $powerType;

    private int $maxPower;

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getOverEnergize(): float
    {
        return $this->overEnergize;
    }

    public function getPowerType(): int
    {
        return $this->powerType;
    }

    public function getMaxPower(): int
    {
        return $this->maxPower;
    }

    #[\Override]
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->amount       = $parameters[0];
        $this->powerType    = $parameters[1];
        $this->overEnergize = $parameters[2] ?? 0;
        $this->maxPower     = $parameters[3] ?? 0;

        return $this;
    }

    public function getOptionalParameterCount(): int
    {
        return 2;
    }

    public function getParameterCount(): int
    {
        return 4;
    }
}
