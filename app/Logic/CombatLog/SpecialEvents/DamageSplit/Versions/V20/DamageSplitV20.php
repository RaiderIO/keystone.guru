<?php

namespace App\Logic\CombatLog\SpecialEvents\DamageSplit\Versions\V20;

use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\DamageSplit\DamageSplitInterface;

/**
 * 6/17 16:05:05.811  SPELL_ABSORBED_SUPPORT,Creature-0-2085-2290-24099-164920-000170B2DE,"Drust Soulcleaver",0xa48,0x0,Player-4184-00867C26,"Vitaminp-TheseGoToEleven",0x512,0x2,322557,"Soul Split",0x20,Player-4184-00867C26,"Vitaminp-TheseGoToEleven",0x512,0x2,413984,"Shifting Sands",0x40,44199,86278,nil,Player-4184-008708AF
 *
 * Functionally the same as SPELL_DAMAGE
 *
 * @author Wouter
 *
 * @since 26/05/2023
 */
class DamageSplitV20 extends SpecialEvent implements DamageSplitInterface
{
    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        return $this;
    }

    public function getParameterCount(): int
    {
        return 38;
    }
}
