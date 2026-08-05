<?php

namespace App\Service\CombatLog\DataExtractors\ImmunityBypasses;

use App\Models\CombatLog\SpellProperty;
use App\Models\Spell\Spell;
use Illuminate\Support\Collection;

/**
 * The immunities whose bypasses are detected. Deliberately conservative: an ability only belongs here when what it
 * stops can be stated exactly, because every unstated exception shows up as a false "bypasses immunity" claim.
 *
 * Not included, and why:
 * - Cloak of Shadows, Nether Ward: their coverage of damage versus harmful effects is version-dependent.
 * - Dispersion, Evasion, Die by the Sword: damage *reduction* or avoidance chance, never immunity.
 */
final class ImmunityDefinitions
{
    /** @var Collection<int, ImmunityDefinitionInterface>|null */
    private static ?Collection $definitions = null;

    /**
     * @return Collection<int, ImmunityDefinitionInterface>
     */
    public static function all(): Collection
    {
        return self::$definitions ??= collect([
            // Immune to all damage and harmful effects
            new ImmunityDefinition(
                SpellProperty::BypassDivineShield,
                Spell::IMMUNITY_DIVINE_SHIELD,
                [Spell::SPELL_DIVINE_SHIELD],
                Spell::SCHOOLS_MASK_ALL,
                true,
                true,
                8000,
            ),
            // Immune to all damage and harmful effects
            new ImmunityDefinition(
                SpellProperty::BypassIceBlock,
                Spell::IMMUNITY_ICE_BLOCK,
                [Spell::SPELL_ICE_BLOCK],
                Spell::SCHOOLS_MASK_ALL,
                true,
                true,
                10000,
            ),
            // Damage only - it never claimed to stop debuffs
            new ImmunityDefinition(
                SpellProperty::BypassAspectOfTheTurtle,
                Spell::IMMUNITY_ASPECT_OF_THE_TURTLE,
                [Spell::SPELL_ASPECT_OF_THE_TURTLE],
                Spell::SCHOOLS_MASK_ALL,
                true,
                false,
                8000,
            ),
            // Physical damage and harmful effects only
            new ImmunityDefinition(
                SpellProperty::BypassBlessingOfProtection,
                Spell::IMMUNITY_BLESSING_OF_PROTECTION,
                [Spell::SPELL_BLESSING_OF_PROTECTION],
                Spell::SCHOOL_PHYSICAL,
                true,
                true,
                10000,
            ),
            // Magical damage and harmful effects only
            new ImmunityDefinition(
                SpellProperty::BypassBlessingOfSpellwarding,
                Spell::IMMUNITY_BLESSING_OF_SPELLWARDING,
                [Spell::SPELL_BLESSING_OF_SPELLWARDING],
                Spell::SCHOOLS_MASK_MAGIC,
                true,
                true,
                10000,
            ),
            // Absorb-based: magic damage still lands (and is absorbed), only harmful magic *effects* are immune.
            // 7s rather than the 5s base - Anti-Magic Barrier extends it by 2s and is commonly talented, and this
            // duration only ever force-closes a window whose removal line is missing
            new ImmunityDefinition(
                SpellProperty::BypassAntiMagicShell,
                Spell::IMMUNITY_ANTI_MAGIC_SHELL,
                [Spell::SPELL_ANTI_MAGIC_SHELL],
                Spell::SCHOOLS_MASK_MAGIC,
                false,
                true,
                7000,
            ),
        ]);
    }
}
