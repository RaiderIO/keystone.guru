<?php

namespace App\Service\CombatLog\DataExtractors\SpellCounters;

use App\Models\CharacterClass;
use App\Models\CombatLog\SpellProperty;
use App\Models\Spell\Spell;

class VanishSpellCounterDefinition implements SpellCounterDefinitionInterface
{
    public const int SPELL_ID_VANISH_CAST = 1856;

    /**
     * The self-aura applied by Vanish - a redundant signal. Detection keys off the
     * SPELL_ID_VANISH_CAST cast instead, kept here only as documented reference.
     */
    public const int SPELL_ID_VANISH_AURA = 11327;

    public function getProperty(): SpellProperty
    {
        return SpellProperty::CounterVanish;
    }

    public function getCounterBit(): int
    {
        return Spell::COUNTER_VANISH;
    }

    /**
     * @return array<int>
     */
    public function getTriggerCastSpellIds(): array
    {
        return [self::SPELL_ID_VANISH_CAST];
    }

    public function getIconName(): string
    {
        return 'ability_vanish';
    }

    public function getCharacterClassKey(): ?string
    {
        return CharacterClass::CHARACTER_CLASS_ROGUE;
    }

    public function getCharacterRaceKey(): ?string
    {
        return null;
    }
}
