<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

class AuraBrokenSpell extends Suffix
{
    private int $extraSpellId;

    private string $extraSpellName;

    private int $extraSchool;

    /** @var string ex. BUFF */
    private string $auraType;

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

    public function getAuraType(): string
    {
        return $this->auraType;
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
        $this->auraType       = $parameters[3];

        return $this;
    }

    public function getParameterCount(): int
    {
        return 4;
    }
}
