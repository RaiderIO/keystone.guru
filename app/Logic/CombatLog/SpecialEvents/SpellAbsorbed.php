<?php

namespace App\Logic\CombatLog\SpecialEvents;

/**
 * SPELL_ABSORBED,Creature-0-4242-1841-14566-131402-0005E285EB,"Underrot Tick",0xa48,0x0,Player-1084-0A5F4542,"Paltalin-TarrenMill",0x10512,0x0,Player-1084-0A5F4542,"Paltalin-TarrenMill",0x10512,0x0,229976,"Blessed Hammer",0x1,1511,7947,nil
 *
 * @package App\Logic\CombatLog\SpecialEvents
 * @author Wouter
 * @since 26/05/2023
 */
class SpellAbsorbed extends SpecialEvent
{
    /**
     * @param array $parameters
     * @return self
     */
    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);


        return $this;
    }

    /**
     * @return int
     */
    public function getOptionalParameterCount(): int
    {
        return 3;
    }


    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 21;
    }


}
