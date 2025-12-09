<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

abstract class SpellBase extends Suffix
{
    private int $extraSpellId;

    private string $extraSpellName;

    private int $extraSchool;

    public function getExtraSpellId(): int
    {
        return $this->extraSpellId;
    }

    public function getExtraSpellName(): string
    {
        return $this->extraSpellName;
    }

    public function getExtraSchool(): int
    {
        return $this->extraSchool;
    }

    /**
     * @return HasParameters|$this
     */
    #[\Override]
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->extraSpellId   = $parameters[0];
        $this->extraSpellName = $parameters[1];
        $this->extraSchool    = $parameters[2];

        return $this;
    }

    public function getParameterCount(): int
    {
        return 3;
    }
}
