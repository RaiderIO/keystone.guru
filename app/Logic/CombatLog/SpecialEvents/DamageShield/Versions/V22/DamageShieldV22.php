<?php

namespace App\Logic\CombatLog\SpecialEvents\DamageShield\Versions\V22;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\SpecialEvents\DamageShield\DamageShieldInterface;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;

/**
 * 3/5/2025 16:55:33.1150  DAMAGE_SHIELD,Player-1105-06BBFB3A,\"Kalrogg-Dalvengyr-EU\",0x512,0x0,Creature-0-1463-2661-16832-214668-00024881BF,\"Venture Co. Patron\",0xa48,0x0,470643,\"Flame Shield\",0x4,Creature-0-1463-2661-16832-214668-00024881BF,0000000000000000,7454956,46917598,0,0,42857,0,0,0,1,0,0,0,2644.56,-4876.98,2335,1.5673,80,1155,1155,-1,4,0,0,0,nil,nil,nil,ST
 *
 * @author Wouter
 *
 * @since 03/04/2025
 */
class DamageShieldV22 extends SpecialEvent implements DamageShieldInterface
{
    /**
     * @return HasParameters|$this
     */
    #[\Override]
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        return $this;
    }

    public function getParameterCount(): int
    {
        return 41;
    }
}
