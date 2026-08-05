<?php

namespace App\Logic\CombatLog\CombatEvents\Prefixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use Override;

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
     * The spell school as a bitmask, ready to be stored in `spells`.`schools_mask`.
     *
     * The raw field is a hex string in every retail log ("0x20"), and a plain (int) cast turns that into 0 rather
     * than 32 - which is how combat-log-created spells ended up school-less (#3845). Decimal is still accepted
     * because the *suffix* schools are logged that way, and a future format change is cheaper to absorb here.
     */
    public function getSpellSchoolMask(): int
    {
        return str_starts_with(strtolower($this->spellSchool), '0x')
            ? (int)hexdec(substr($this->spellSchool, 2))
            : (int)$this->spellSchool;
    }

    #[Override]
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->spellId     = $parameters[0];
        $this->spellName   = $parameters[1];
        $this->spellSchool = $parameters[2];

        return $this;
    }

    public function getParameterCount(): int
    {
        return 3;
    }
}
