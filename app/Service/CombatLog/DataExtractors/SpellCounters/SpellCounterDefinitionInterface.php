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
     * Player buff SPELL_AURA_APPLIED spell ids that mark the moment this counter takes effect. Needed for counters
     * whose effect does not coincide with their cast - Invisibility fades in over 3 seconds, so its cast lies far
     * outside the correlation window of the drop it eventually causes.
     *
     * @return array<int>
     */
    public function getTriggerAuraSpellIds(): array;

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

    /**
     * Whether this counter makes NPCs lose their target. Only such a counter can explain an NPC abandoning a cast
     * (signature C) - one that merely strips debuffs off the player has no mechanism to do so, and enabling
     * signature C for it would credit it with every unrelated abandoned cast in its window.
     */
    public function dropsThreat(): bool;

    /**
     * The `Spell::DISPEL_TYPE_*` values that rule a removed debuff out as something this counter could have caused
     * to go away (signatures A and B). Empty for a threat drop - it makes the NPC give up on the player regardless
     * of what it had applied. Cloak of Shadows can only strip magic, so a poison or disease falling off in its
     * window is provably not its doing.
     *
     * Only positively contradicting types belong here. A dispel type that is merely unresolved - `n_a`, `unknown`,
     * or the empty string combat-log-created spells carry until their Wowhead data is fetched - must not be listed,
     * or the filter would reject the very spells this extractor exists to discover.
     *
     * @return array<string>
     */
    public function getUnstrippableDebuffDispelTypes(): array;
}
