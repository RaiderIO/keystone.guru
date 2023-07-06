<?php

namespace App\Logic\CombatLog\SpecialEvents;

use App\Logic\CombatLog\CombatEvents\GenericData;
use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

/**
 * 5/27 13:08:17.696  SPELL_RESURRECT,Player-3676-0E1A2816,"Dudeurdead-Area52",0x512,0x0,Player-63-0CD36083,"Bunnicula-Ysera",0x10512,0x0,7328,"Redemption",0x2
 *
 * @package App\Logic\CombatLog\SpecialEvents
 * @author Wouter
 * @since 26/05/2023
 */
class SpellResurrect extends GenericSpecialEvent
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
    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        $this->spellId     = $parameters[8];
        $this->spellName   = $parameters[9];
        $this->spellSchool = $parameters[10];

        return $this;
    }

    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 11;
    }
}
