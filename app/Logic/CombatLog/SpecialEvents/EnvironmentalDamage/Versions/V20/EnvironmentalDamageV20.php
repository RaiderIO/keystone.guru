<?php

namespace App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage\Versions\V20;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;

/**
 * ENVIRONMENTAL_DAMAGE,0000000000000000,nil,0x80000000,0x80000000,Player-1084-0A6D63A6,"Sadarøn-TarrenMill",0x512,0x0,Player-1084-0A6D63A6,0000000000000000,234352,370660,8542,2087,3228,0,3,100,100,0,-141.31,-821.74,2080,2.0058,407,Falling,112372,112372,0,1,0,0,0,nil,nil,nil
 *
 * @author Wouter
 *
 * @since 27/05/2023
 */
class EnvironmentalDamageV20 extends SpecialEvent
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
        return 36;
    }
}
