<?php

namespace App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage\Versions\V22;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;

/**
 * 11:45:23.5980  ENVIRONMENTAL_DAMAGE,0000000000000000,nil,0x80000000,0x80000000,Player-1335-09E309B2,\"Éclairochoco-Ysondre-EU\",0x512,0x0,Player-1335-09E309B2,0000000000000000,6585299,6612606,71503,17650,36151,66,300,0,0,2500000,2500000,0,-460.68,-370.82,293,5.7054,630,Falling,27307,27307,0,1,0,0,0,nil,nil,nil
 *
 * @author Wouter
 *
 * @since 23/12/2024
 */
class EnvironmentalDamageV22 extends SpecialEvent
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
        return 38;
    }
}
