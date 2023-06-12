<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

abstract class SpellBase extends Suffix
{
    private int $extraSpellId;

    private string $extraSpellName;

    private int $extraSchool;

    /**
     * @return int
     */
    public function getExtraSpellId(): int
    {
        return $this->extraSpellId;
    }

    /**
     * @return string
     */
    public function getExtraSpellName(): string
    {
        return $this->extraSpellName;
    }

    /**
     * @return int
     */
    public function getExtraSchool(): int
    {
        return $this->extraSchool;
    }

    /**
     * @param array $parameters
     * @return HasParameters|$this
     */
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->extraSpellId   = $parameters[0];
        $this->extraSpellName = $parameters[1];
        $this->extraSchool    = $parameters[2];

        return $this;
    }

    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 3;
    }
}
