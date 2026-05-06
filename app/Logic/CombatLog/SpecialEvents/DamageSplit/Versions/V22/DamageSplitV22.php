<?php

namespace App\Logic\CombatLog\SpecialEvents\DamageSplit\Versions\V22;

use App\Logic\CombatLog\SpecialEvents\DamageSplit\DamageSplitInterface;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;

/**
 * 11/9/2024 23:12:55.6541  DAMAGE_SPLIT,Player-1084-0B0A5965,\"Wolflocks-TarrenMill-EU\",0x512,0x0,Pet-0-4251-2662-13168-17252-0103DFDE2B,\"Haafaran\",0x1112,0x0,108446,\"Soul Link\",0x20,Pet-0-4251-2662-13168-17252-0103DFDE2B,Player-1084-0B0A5965,4508536,4508536,59318,90782,103612,0,0,407193,3,95,200,0,1446.92,-265.56,2359,4.5983,610,0,0,-1,32,0,0,43660,nil,nil,nil,AOE
 *
 * Functionally the same as SPELL_DAMAGE
 *
 * @author Wouter
 *
 * @since 26/05/2023
 */
class DamageSplitV22 extends SpecialEvent implements DamageSplitInterface
{
    #[\Override]
    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        return $this;
    }

    public function getParameterCount(): int
    {
        return 41;
    }
}
