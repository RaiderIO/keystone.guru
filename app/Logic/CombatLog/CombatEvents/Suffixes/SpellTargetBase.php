<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

abstract class SpellTargetBase extends SpellBase
{
    private string $auraType;

    public function getAuraType(): string
    {
        return $this->auraType;
    }

    /**
     * @return HasParameters|$this
     */
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->auraType = $parameters[3];

        return $this;
    }

    public function getParameterCount(): int
    {
        return 4;
    }
}
