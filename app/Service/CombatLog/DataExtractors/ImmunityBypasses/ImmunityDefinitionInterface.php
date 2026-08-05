<?php

namespace App\Service\CombatLog\DataExtractors\ImmunityBypasses;

use App\Models\CombatLog\SpellProperty;

/**
 * Describes one player immunity window and - crucially - what that immunity is actually supposed to stop. A hit that
 * lands on something the immunity never covered (physical damage during Anti-Magic Shell, a debuff during Aspect of
 * the Turtle) is expected behaviour, not a bypass, so every definition declares its own coverage.
 */
interface ImmunityDefinitionInterface
{
    /**
     * The SpellProperty case a bypass of this immunity is tracked under.
     */
    public function getProperty(): SpellProperty;

    /**
     * The Spell::IMMUNITY_* bit this immunity occupies on `bypasses_immunities_mask`.
     */
    public function getImmunityBit(): int;

    /**
     * @return array<int> player buff spell ids whose SPELL_AURA_APPLIED opens this immunity window
     */
    public function getBuffSpellIds(): array;

    /**
     * The `Spell::SCHOOL_*` bits this immunity protects against. Damage of any other school landing during the window
     * was never supposed to be stopped.
     */
    public function getProtectedSchoolsMask(): int;

    /**
     * Whether damage landing during the window is a bypass. False for absorb-based defensives (Anti-Magic Shell),
     * where damage reaching the player is how the ability works rather than a failure of it.
     */
    public function blocksDamage(): bool;

    /**
     * Whether a harmful aura applied during the window is a bypass. False for damage-only defensives (Aspect of the
     * Turtle), which never claimed to stop debuffs.
     */
    public function blocksHarmfulAuras(): bool;

    /**
     * The buff's full duration in milliseconds. Only used to close a window whose SPELL_AURA_REMOVED never arrives
     * (a truncated log, a player leaving the instance); the removal is the authority whenever it is present. No slack
     * is added on purpose - an over-long window would manufacture bypasses.
     */
    public function getMaxDurationMs(): int;
}
