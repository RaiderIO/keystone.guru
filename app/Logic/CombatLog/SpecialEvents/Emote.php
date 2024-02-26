<?php

namespace App\Logic\CombatLog\SpecialEvents;

/**
 * EMOTE,Creature-0-4242-1841-14566-131318-00006285EA,"Elder Leaxa",0000000000000000,nil,|TINTERFACE\ICONS\INV_TikiMan2_Bloodtroll.blp:20|t Elder Leaxa begins to cast |cFFF00000|Hspell:264603|h[Blood Mirror]|h|r
 * This line causes an issue because the entry is not escaped properly and its name contains a comma, doh
 * EMOTE,Creature-0-3019-2519-13090-189340-00005D7992,"Chargath, Bane of Scales",Player-3684-0D90C5CC,"Novakk",|TInterface\Icons\Spell_Nature_Slow.blp:20|tChargath, Bane of Scales targets you with |cFFFF0000|Hspell:373424|h[Grounding Spear]|h|r!
 *
 * @author Wouter
 *
 * @since 26/05/2023
 */
class Emote extends SpecialEvent
{
    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        return $this;
    }

    public function getOptionalParameterCount(): int
    {
        return 1;
    }

    public function getParameterCount(): int
    {
        return 6;
    }
}
