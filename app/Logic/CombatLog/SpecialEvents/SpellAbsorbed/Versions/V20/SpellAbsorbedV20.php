<?php

namespace App\Logic\CombatLog\SpecialEvents\SpellAbsorbed\Versions\V20;

use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\SpellAbsorbed\SpellAbsorbedInterface;

/**
 * SPELL_ABSORBED,Creature-0-4242-1841-14566-131402-0005E285EB,"Underrot Tick",0xa48,0x0,Player-1084-0A5F4542,"Paltalin-TarrenMill",0x10512,0x0,Player-1084-0A5F4542,"Paltalin-TarrenMill",0x10512,0x0,229976,"Blessed Hammer",0x1,1511,7947,nil
 *
 * @author Wouter
 *
 * @since 26/05/2023
 */
class SpellAbsorbedV20 extends SpecialEvent implements SpellAbsorbedInterface
{
    #[\Override]
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
        return 21;
    }
}
