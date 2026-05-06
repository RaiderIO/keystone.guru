<?php

namespace App\Logic\CombatLog\SpecialEvents\DamageShield\Versions\V9SoD;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\SpecialEvents\DamageShield\DamageShieldInterface;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;

/**
 * 12/9/2024 19:07:44.9720  DAMAGE_SHIELD,Player-5827-01C9B4F1,"Latto-LivingFlame-EU",0x514,0x0,Creature-0-5208-531-679-15264-0002D73C0B,"Anubisath Sentinel",0xa48,0x0,9910,"Thorns",0x8,Creature-0-5208-531-679-15264-0002D73C0B,0000000000000000,564899,565920,0,0,0,0,0,-1,0,0,0,-8172.28,2109.30,0,0.8244,61,32,32,-1,8,0,0,0,nil,nil,nil,ST
 *
 * @author Wouter
 *
 * @since 14/01/2025
 */
class DamageShieldV9SoD extends SpecialEvent implements DamageShieldInterface
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
        return 40;
    }
}
