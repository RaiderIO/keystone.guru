<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

abstract class SpellTargetBase extends SpellBase
{
    private string $auraType;

    /**
     * @return string
     */
    public function getAuraType(): string
    {
        return $this->auraType;
    }

    /**
     * @param array $parameters
     * @return HasParameters|$this
     */
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->auraType = $parameters[3];

        return $this;
    }

    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 4;
    }
}
