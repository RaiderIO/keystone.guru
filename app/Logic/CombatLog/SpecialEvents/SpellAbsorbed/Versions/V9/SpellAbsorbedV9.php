<?php

namespace App\Logic\CombatLog\SpecialEvents\SpellAbsorbed\Versions\V9;

use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\SpellAbsorbed\SpellAbsorbedInterface;

/**
 * SPELL_ABSORBED,Player-4904-00C3DFF5,"Riowak-ClassicPTRRealm1",0x511,0x0,Creature-0-4908-129-1352-7337-00018DFF75,"Death's Head Necromancer",0x10a48,0x0,Creature-0-4908-129-1352-7337-00018DFF75,"Death's Head Necromancer",0x10a48,0x0,11445,"Bone Armor",0x20,309,400
 *
 * @author Wouter
 *
 * @since 26/05/2023
 */
class SpellAbsorbedV9 extends SpecialEvent implements SpellAbsorbedInterface
{
    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        return $this;
    }

    public function getOptionalParameterCount(): int
    {
        return 3;
    }

    public function getParameterCount(): int
    {
        return 20;
    }
}
