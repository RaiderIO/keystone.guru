<?php

namespace App\Logic\CombatLog\SpecialEvents;

/**
 * PARTY_KILL,Player-1084-0A6D63A6,"Sadarøn-TarrenMill",0x512,0x0,Creature-0-4242-1841-14566-131402-0005E285EB,"Underrot Tick",0xa48,0x0,0
 *
 * @package App\Logic\CombatLog\SpecialEvents
 * @author Wouter
 * @since 26/05/2023
 */
class PartyKill extends SpecialEvent
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
    public function getParameterCount(): int
    {
        return 9;
    }


}
