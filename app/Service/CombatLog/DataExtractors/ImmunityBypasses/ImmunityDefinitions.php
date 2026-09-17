<?php

namespace App\Service\CombatLog\DataExtractors\ImmunityBypasses;

use App\Models\CombatLog\SpellProperty;
use App\Models\Spell\KnownSpell;
use App\Models\Spell\SpellImmunity;
use App\Models\Spell\SpellSchool;
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
                SpellImmunity::DivineShield->value,
                [KnownSpell::DivineShield->value],
                SpellSchool::MASK_ALL,
                true,
                true,
                8000,
            ),
            // Immune to all damage and harmful effects
            new ImmunityDefinition(
                SpellProperty::BypassIceBlock,
                SpellImmunity::IceBlock->value,
                [KnownSpell::IceBlock->value],
                SpellSchool::MASK_ALL,
                true,
                true,
                10000,
            ),
            // Damage only - it never claimed to stop debuffs
            new ImmunityDefinition(
                SpellProperty::BypassAspectOfTheTurtle,
                SpellImmunity::AspectOfTheTurtle->value,
                [KnownSpell::AspectOfTheTurtle->value],
                SpellSchool::MASK_ALL,
                true,
                false,
                8000,
            ),
            // Physical damage and harmful effects only
            new ImmunityDefinition(
                SpellProperty::BypassBlessingOfProtection,
                SpellImmunity::BlessingOfProtection->value,
                [KnownSpell::BlessingOfProtection->value],
                SpellSchool::Physical->value,
                true,
                true,
                10000,
            ),
            // Magical damage and harmful effects only
            new ImmunityDefinition(
                SpellProperty::BypassBlessingOfSpellwarding,
                SpellImmunity::BlessingOfSpellwarding->value,
                [KnownSpell::BlessingOfSpellwarding->value],
                SpellSchool::MASK_MAGIC,
                true,
                true,
                10000,
            ),
            // Absorb-based: magic damage still lands (and is absorbed), only harmful magic *effects* are immune.
            // 7s rather than the 5s base - Anti-Magic Barrier extends it by 2s and is commonly talented, and this
            // duration only ever force-closes a window whose removal line is missing
            new ImmunityDefinition(
                SpellProperty::BypassAntiMagicShell,
                SpellImmunity::AntiMagicShell->value,
                [KnownSpell::AntiMagicShell->value],
                SpellSchool::MASK_MAGIC,
                false,
                true,
                7000,
            ),
        ]);
    }
}
