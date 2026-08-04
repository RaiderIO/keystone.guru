<?php

namespace App\Service\CombatLog\DataExtractors\SpellCounters;

use App\Models\CharacterRace;
use App\Models\CombatLog\SpellProperty;
use App\Models\Spell\Spell;

class ShadowmeldSpellCounterDefinition implements SpellCounterDefinitionInterface
{
    public const int SPELL_ID_SHADOWMELD = 58984;

    public function getProperty(): SpellProperty
    {
        return SpellProperty::CounterShadowmeld;
    }

    public function getCounterBit(): int
    {
        return Spell::COUNTER_SHADOWMELD;
    }

    /**
     * @return array<int>
     */
    public function getTriggerCastSpellIds(): array
    {
        return [self::SPELL_ID_SHADOWMELD];
    }

    public function getIconName(): string
    {
        return 'ability_ambush';
    }

    public function getCharacterClassKey(): ?string
    {
        return null;
    }

    public function getCharacterRaceKey(): ?string
    {
        return CharacterRace::CHARACTER_RACE_NIGHT_ELF;
    }
}
