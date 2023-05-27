<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

class Drain extends Leech
{

    private int $maxPower;

    /**
     * @return int
     */
    public function getMaxPower(): int
    {
        return $this->maxPower;
    }

    /**
     * @param array $parameters
     * @return HasParameters
     */
    public function setParameters(array $parameters): HasParameters
    {
        if (isset($parameters[3])) {
            $this->maxPower = $parameters[3];
        }

        return $this;
    }


    /**
     * @return int
     */
    public function getOptionalParameterCount(): int
    {
        return 1;
    }

    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 4;
    }
}
