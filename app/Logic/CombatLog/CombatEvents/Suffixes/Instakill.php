<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

class Instakill extends Suffix
{
    private bool $unconsciousOnDeath;

    /**
     * @return bool
     */
    public function isUnconsciousOnDeath(): bool
    {
        return $this->unconsciousOnDeath;
    }

    /**
     * @param array $parameters
     * @return HasParameters
     */
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        if (isset($parameters[0])) {
            $this->unconsciousOnDeath = (bool)$parameters[0];
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
        return 1;
    }
}
