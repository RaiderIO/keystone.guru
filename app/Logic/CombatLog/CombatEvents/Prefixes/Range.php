<?php

namespace App\Logic\CombatLog\CombatEvents\Prefixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

class Range extends Prefix
{
    private int $spellId;

    private string $spellName;

    private string $spellSchool;

    public function getSpellId(): int
    {
        return $this->spellId;
    }

    public function getSpellName(): string
    {
        return $this->spellName;
    }

    public function getSpellSchool(): string
    {
        return $this->spellSchool;
    }

    /**
     * @return self
     */
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->spellId = $parameters[0];
        $this->spellName = $parameters[1];
        $this->spellSchool = $parameters[2];

        return $this;
    }

    public function getParameterCount(): int
    {
        return 3;
    }
}
