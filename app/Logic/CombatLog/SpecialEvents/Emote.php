<?php

namespace App\Logic\CombatLog\SpecialEvents;

/**
 * EMOTE,Creature-0-4242-1841-14566-131318-00006285EA,"Elder Leaxa",0000000000000000,nil,|TINTERFACE\ICONS\INV_TikiMan2_Bloodtroll.blp:20|t Elder Leaxa begins to cast |cFFF00000|Hspell:264603|h[Blood Mirror]|h|r
 *
 * @package App\Logic\CombatLog\SpecialEvents
 * @author Wouter
 * @since 26/05/2023
 */
class Emote extends SpecialEvent
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
        return 5;
    }
}
