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

    #[\Override]
    public function setParameters(array $parameters): HasParameters
    {
        if (isset($parameters[3])) {
            $this->maxPower = $parameters[3];
        }

        return $this;
    }

    #[\Override]
    public function getOptionalParameterCount(): int
    {
        return 1;
    }

    #[\Override]
    public function getParameterCount(): int
    {
        return 4;
    }
}
