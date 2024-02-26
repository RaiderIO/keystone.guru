<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

class Energize extends Suffix
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

    /**
     * @return HasParameters|$this
     */
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->amount = $parameters[0];
        $this->overEnergize = $parameters[1];
        $this->powerType = $parameters[2];

        if (isset($parameters[3])) {
            $this->maxPower = $parameters[3];
        }

        return $this;
    }

    public function getOptionalParameterCount(): int
    {
        return 1;
    }

    public function getParameterCount(): int
    {
        return 4;
    }
}
