<?php

namespace App\Service\CombatLog\DataExtractors\SpellCounters;

use App\Models\CharacterClass;
use App\Models\CombatLog\SpellProperty;
use App\Models\Spell\Spell;

/**
 * Unlike the threat-drop counters, Cloak of Shadows does not pre-empt anything - it strips the magic debuffs already
 * on the rogue the instant it goes up. Only the debuff-removal signatures apply to it, and only for magic debuffs.
 */
class CloakOfShadowsSpellCounterDefinition extends SpellCounterDefinition
{
    /** Cast and self-aura share this id, and both are logged at the same instant. */
    public const int SPELL_ID_CLOAK_OF_SHADOWS = 31224;

    public function getProperty(): SpellProperty
    {
        return SpellProperty::CounterCloakOfShadows;
    }

    public function getCounterBit(): int
    {
        return Spell::COUNTER_CLOAK_OF_SHADOWS;
    }

    /**
     * @return array<int>
     */
    public function getTriggerCastSpellIds(): array
    {
        return [self::SPELL_ID_CLOAK_OF_SHADOWS];
    }

    /**
     * The strip is done by the aura, not by the cast - both are logged at the same instant, but keying off the aura
     * as well means a cast success that is missing from the log does not lose the detection.
     *
     * @return array<int>
     */
    public function getTriggerAuraSpellIds(): array
    {
        return [self::SPELL_ID_CLOAK_OF_SHADOWS];
    }

    public function getIconName(): string
    {
        return 'spell_shadow_nethercloak';
    }

    public function getCharacterClassKey(): ?string
    {
        return CharacterClass::CHARACTER_CLASS_ROGUE;
    }

    public function dropsThreat(): bool
    {
        return false;
    }

    /**
     * `Spell::DISPEL_TYPE_NONE` is deliberately absent even though "not dispellable at all"
     * contradicts a magic strip as squarely as a poison does: `2026_04_20_224500_update_spell_dispel_type_to_translation_keys`
     * folded the legacy empty string into `none` alongside the explicit `None`, so the value no
     * longer distinguishes "known to be undispellable" from "never had a value" - and rejecting
     * on it would rule out spells that only look undispellable because nobody filled the field.
     *
     * @return array<string>
     */
    public function getUnstrippableDebuffDispelTypes(): array
    {
        return [
            Spell::DISPEL_TYPE_POISON,
            Spell::DISPEL_TYPE_DISEASE,
            Spell::DISPEL_TYPE_CURSE,
            Spell::DISPEL_TYPE_ENRAGE,
        ];
    }
}
