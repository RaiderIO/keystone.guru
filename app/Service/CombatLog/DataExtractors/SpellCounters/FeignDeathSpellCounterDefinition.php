<?php

namespace App\Service\CombatLog\DataExtractors\SpellCounters;

use App\Models\CharacterClass;
use App\Models\CombatLog\SpellProperty;
use App\Models\Spell\Spell;

class FeignDeathSpellCounterDefinition extends SpellCounterDefinition
{
    /** Cast and self-aura share this id, and both are logged at the same instant. */
    public const int SPELL_ID_FEIGN_DEATH = 5384;

    public function getProperty(): SpellProperty
    {
        return SpellProperty::CounterFeignDeath;
    }

    public function getCounterBit(): int
    {
        return Spell::COUNTER_FEIGN_DEATH;
    }

    /**
     * @return array<int>
     */
    public function getTriggerCastSpellIds(): array
    {
        return [self::SPELL_ID_FEIGN_DEATH];
    }

    public function getIconName(): string
    {
        return 'ability_rogue_feigndeath';
    }

    public function getCharacterClassKey(): ?string
    {
        return CharacterClass::CHARACTER_CLASS_HUNTER;
    }
}
