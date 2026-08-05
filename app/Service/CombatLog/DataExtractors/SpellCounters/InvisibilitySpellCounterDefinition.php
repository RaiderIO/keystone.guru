<?php

namespace App\Service\CombatLog\DataExtractors\SpellCounters;

use App\Models\CharacterClass;
use App\Models\CombatLog\SpellProperty;
use App\Models\Spell\Spell;

class InvisibilitySpellCounterDefinition extends SpellCounterDefinition
{
    /**
     * The cast of Invisibility. Deliberately NOT a trigger: it only starts a 3 second fade, and the threat drop
     * happens when SPELL_ID_INVISIBILITY_AURA lands at the end of it.
     */
    public const int SPELL_ID_INVISIBILITY_CAST = 66;

    /** The invisible state proper, applied 3 seconds after the cast - this is the moment threat is dropped. */
    public const int SPELL_ID_INVISIBILITY_AURA = 32612;

    /** Greater Invisibility (Arcane) has no fade - its cast and aura land together. */
    public const int SPELL_ID_GREATER_INVISIBILITY = 110959;

    public function getProperty(): SpellProperty
    {
        return SpellProperty::CounterInvisibility;
    }

    public function getCounterBit(): int
    {
        return Spell::COUNTER_INVISIBILITY;
    }

    /**
     * @return array<int>
     */
    public function getTriggerCastSpellIds(): array
    {
        return [self::SPELL_ID_GREATER_INVISIBILITY];
    }

    /**
     * @return array<int>
     */
    public function getTriggerAuraSpellIds(): array
    {
        return [self::SPELL_ID_INVISIBILITY_AURA, self::SPELL_ID_GREATER_INVISIBILITY];
    }

    public function getIconName(): string
    {
        return 'ability_mage_invisibility';
    }

    public function getCharacterClassKey(): ?string
    {
        return CharacterClass::CHARACTER_CLASS_MAGE;
    }
}
