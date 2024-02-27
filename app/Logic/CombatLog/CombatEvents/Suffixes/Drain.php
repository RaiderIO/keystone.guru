<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

class Drain extends Leech
{
    private int $maxPower;

    public function getMaxPower(): int
    {
        return $this->maxPower;
    }

    public function setParameters(array $parameters): HasParameters
    {
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
