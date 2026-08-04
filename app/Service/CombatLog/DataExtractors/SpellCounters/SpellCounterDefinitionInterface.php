<?php

namespace App\Service\CombatLog\DataExtractors\SpellCounters;

use App\Models\CombatLog\SpellProperty;

interface SpellCounterDefinitionInterface
{
    /**
     * The SpellProperty case this counter is tracked under.
     */
    public function getProperty(): SpellProperty;

    /**
     * The Spell::COUNTER_* bit this counter occupies on `counters_mask`.
     */
    public function getCounterBit(): int;

    /**
     * @return array<int> player SPELL_CAST_SUCCESS spell ids that mark a use of this counter
     */
    public function getTriggerCastSpellIds(): array;

    /**
     * The spell icon name, rendered via `ksgAssetImage('spells/<icon>.jpg')`.
     */
    public function getIconName(): string;

    /**
     * The CharacterClass `key` this counter is a class ability of, e.g. 'rogue'. Null when this
     * counter is a racial ability instead. Exactly one of class/race key is non-null.
     */
    public function getCharacterClassKey(): ?string;

    /**
     * The CharacterRace `key` this counter is a racial ability of. Null when this counter is a
     * class ability instead. Exactly one of class/race key is non-null.
     */
    public function getCharacterRaceKey(): ?string;
}
