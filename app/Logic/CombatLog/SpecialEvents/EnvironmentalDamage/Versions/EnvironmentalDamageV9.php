<?php

namespace App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage\Versions;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;

/**
 *  ENVIRONMENTAL_DAMAGE,0000000000000000,nil,0x80000000,0x80000000,Player-4904-00C3DFF5,"Riowak-ClassicPTRRealm1",0x511,0x0,Player-4904-00C3DFF5,0000000000000000,100,100,2956,0,4975,3,100,10  0,0,2158.50,1552.46,301,4.4448,199,Fire,10,10,0,4,0,0,0,nil,nil,nil
 *
 * @author Wouter
 *
 * @since 27/05/2023
 */
class EnvironmentalDamageV9 extends SpecialEvent
{
    /**
     * @return HasParameters|$this
     */
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        return $this;
    }

    public function getParameterCount(): int
    {
        return 35;
    }
}
