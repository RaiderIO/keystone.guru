<?php

namespace App\Logic\CombatLog\CombatEvents\Prefixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

class Range extends Prefix
{
    private int $spellId;

    private string $spellName;

    private string $spellSchool;

    /**
     * @return int
     */
    public function getSpellId(): int
    {
        return $this->spellId;
    }

    /**
     * @return string
     */
    public function getSpellName(): string
    {
        return $this->spellName;
    }

    /**
     * @return string
     */
    public function getSpellSchool(): string
    {
        return $this->spellSchool;
    }

    /**
     * @param array $parameters
     * @return self
     */
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->spellId     = $parameters[0];
        $this->spellName   = $parameters[1];
        $this->spellSchool = $parameters[2];

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
