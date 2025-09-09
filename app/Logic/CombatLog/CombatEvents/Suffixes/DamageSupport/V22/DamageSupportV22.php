<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\DamageSupport\V22;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\CombatEvents\Suffixes\Damage\V20\DamageV20;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageSupport\DamageSupportInterface;
use App\Logic\CombatLog\Guid\Guid;

/**
 * 11/24/2024 13:01:06.0290  SPELL_DAMAGE_SUPPORT,Player-1084-0A2CAD7D,"Krabix-TarrenMill-EU",0x511,0x0,Creature-0-4247-2290-7331-165111-000143237F,"Drust Spiteclaw",0xa48,0x0,360828,"Blistering Scales",0xc,Creature-0-4247-2290-7331-165111-000143237F,0000000000000000,49136123,49467365,0,0,42857,0,0,0,1,0,0,0,-6979.75,1878.09,1669,0.9612,80,43331,21240,-1,12,0,0,0,1,nil,nil,Player-1401-0A4CFE3A
 *
 * This does not have the DamageType field that you'd find in DamageV22, so it's extending DamageV20 instead.
 */
class DamageSupportV22 extends DamageV20 implements DamageSupportInterface
{

    private Guid $supportGuid;

    public function getSupportGuid(): ?Guid
    {
        return $this->supportGuid;
    }

    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->supportGuid = Guid::createFromGuidString($parameters[10]);

        return $this;
    }

    public function getParameterCount(): int
    {
        return 11;
    }
}
