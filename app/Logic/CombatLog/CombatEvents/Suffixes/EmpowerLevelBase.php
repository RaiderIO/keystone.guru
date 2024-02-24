<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

abstract class EmpowerLevelBase extends Suffix
{
    private int $powerLevel;

    /**
     * @return int
     */
    public function getPowerLevel(): int
    {
        return $this->powerLevel;
    }

    /**
     * @param array $parameters
     * @return HasParameters
     */
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->powerLevel = (int)$parameters[0];

        return $this;
    }


    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 1;
    }
}
