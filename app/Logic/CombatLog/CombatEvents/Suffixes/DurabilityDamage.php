<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

/**
 * 8/6 19:41:38.542  SPELL_DURABILITY_DAMAGE,Creature-0-5208-409-13456-228438-000032600F,"Ragnaros",0x10a48,0x0,Player-5827-02611E00,"Thickd-LivingFlame",0x514,0x0,21388,"Melt Weapon",0x4,227833,"Glaive of Obsidian Fury"
 */
class DurabilityDamage extends Suffix
{
    private int $itemId;

    private string $itemName;

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function getItemName(): string
    {
        return $this->itemName;
    }

    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->itemId   = (int)$parameters[0];
        $this->itemName = $parameters[1];

        return $this;
    }

    public function getParameterCount(): int
    {
        return 2;
    }
}
