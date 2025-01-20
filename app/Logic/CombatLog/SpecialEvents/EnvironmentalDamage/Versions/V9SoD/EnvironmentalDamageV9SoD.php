<?php

namespace App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage\Versions\V9SoD;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;

/**
 * 12/9/2024 19:19:22.2240  ENVIRONMENTAL_DAMAGE,0000000000000000,nil,0x80000000,0x80000000,Player-5827-021AC5E3,"Kduskwood-LivingFlame-EU",0x514,0x0,Player-5827-021AC5E3,0000000000000000,91,100,0,0,0,0,0,-1,0,0,0,-8341.93,2064.86,0,1.2897,71,Falling,752,752,0,1,0,0,0,nil,nil,nil
 *
 * @author Wouter
 *
 * @since 14/01/2025
 */
class EnvironmentalDamageV9SoD extends SpecialEvent
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
        return 37;
    }
}
