<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

/**
 * @TODO This seems to be a SpecialEvent that behaves like a regular SPELL_ event with a LEECH suffix. But in V22
 * two new unknown fields were added to AdvancedData but only 1 was added to this event? So this suffix is offset by 1
 * in that regard - since this is only for SoD I'm not fixing it _now_ but for SoD this event is broken. See the below combat log.
 *
 * SPELL_LEECH,Creature-0-5208-531-679-15262-0001573C0B,"Obsidian Eradicator",0x10a48,0x0,Player-5827-01CB5FED,"Manta-LivingFlame-EU",0x514,0x0,1215781,"Drain Mana",0x20,Player-5827-01CB5FED,0000000000000000,97,100,0,0,0,0,0,-1,0,0,0,-8204.26,2063.29,0,1.1711,73,125,0,250,3527
 */
class Leech extends Suffix
{
    private float $amount;

    private float $overEnergize;

    private int $powerType;

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getOverEnergize(): float
    {
        return $this->overEnergize;
    }

    public function getPowerType(): int
    {
        return $this->powerType;
    }

    /**
     * @return HasParameters|$this
     */
    #[\Override]
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->amount       = $parameters[0];
        $this->overEnergize = $parameters[1];
        $this->powerType    = $parameters[2];

        return $this;
    }

    public function getOptionalParameterCount(): int
    {
        return 1;
    }

    public function getParameterCount(): int
    {
        return 4;
    }
}
