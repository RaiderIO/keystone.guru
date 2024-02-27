<?php

namespace App\Logic\CombatLog\SpecialEvents\DamageShieldMissed\Versions;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\SpecialEvents\DamageShield\DamageShieldInterface;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;

/**
 * DAMAGE_SHIELD,Creature-0-3113-349-4894-12219-000070F2DD,"Barbed Lasher",0xa48,0x0,Player-1084-0A5F82CD,"Laroniá-TarrenMill",0x511,0x0,9464,"Barbs",0x8,Player-1084-0A5F82CD,0000000000000000,404536,404536,9823,2086,3157,0,3,60,100,0,1049.25,-383.47,280,1.8750,401,0,0,-1,8,0,0,0,nil,nil,nil
 *
 * @author Wouter
 *
 * @since 31/08/2023
 */
class DamageShieldMissedV9 extends SpecialEvent implements DamageShieldInterface
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
        return 14;
    }
}
