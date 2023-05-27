<?php

namespace App\Logic\CombatLog\SpecialEvents;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

/**
 *  DAMAGE_SPLIT,Player-1084-0A5FB91A,"Tyrsael-TarrenMill",0x511,0x0,Player-1084-0A61C409,"Loxford-TarrenMill",0x512,0x0,6940,"Blessing of Sacrifice",0x2,Player-1084-0A61C409,0000000000000000,541700,541700,12109,2174,10833,226592,0,49267,50000,0,-39.06,-832.58,2080,3.3811,435,0,0,-1,1,0,0,17982,nil,nil,nil
 *
 * @package App\Logic\CombatLog\SpecialEvents
 * @author Wouter
 * @since 27/05/2023
 */
class DamageSplit extends SpecialEvent
{
    /**
     * @param array $parameters
     * @return HasParameters|$this
     */
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        return $this;
    }

    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 38;
    }
}
