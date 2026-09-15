<?php

namespace App\Models\CombatLog;

use App\Models\Spell\Spell;
use LogicException;

enum SpellProperty: string
{
    case Aura                  = 'aura';
    case Debuff                = 'debuff';
    case MissAbsorb            = 'miss_absorb';
    case MissBlock             = 'miss_block';
    case MissDeflect           = 'miss_deflect';
    case MissDodge             = 'miss_dodge';
    case MissEvade             = 'miss_evade';
    case MissImmune            = 'miss_immune';
    case MissMiss              = 'miss_miss';
    case MissParry             = 'miss_parry';
    case MissReflect           = 'miss_reflect';
    case MissResist            = 'miss_resist';
    case MissInterrupt         = 'miss_interrupt';
    case CounterVanish         = 'counter_vanish';
    case CounterShadowmeld     = 'counter_shadowmeld';
    case CounterFeignDeath     = 'counter_feign_death';
    case CounterInvisibility   = 'counter_invisibility';
    case CounterCloakOfShadows = 'counter_cloak_of_shadows';

    case BypassDivineShield           = 'bypass_divine_shield';
    case BypassIceBlock               = 'bypass_ice_block';
    case BypassAspectOfTheTurtle      = 'bypass_aspect_of_the_turtle';
    case BypassBlessingOfProtection   = 'bypass_blessing_of_protection';
    case BypassBlessingOfSpellwarding = 'bypass_blessing_of_spellwarding';
    case BypassAntiMagicShell         = 'bypass_anti_magic_shell';

    public static function fromMissTypeBit(int $bit): self
    {
        return self::from(sprintf('miss_%s', Spell::ALL_MISS_TYPES[$bit]));
    }

    /**
     * The `spells` column this property is stored in.
     */
    public function column(): string
    {
        return match (true) {
            $this === self::Aura      => 'aura',
            $this === self::Debuff    => 'debuff',
            $this->isCounter()        => 'counters_mask',
            $this->isImmunityBypass() => 'bypasses_immunities_mask',
            default                   => 'miss_types_mask',
        };
    }

    /**
     * The bit this property occupies within its mask column, or null for the two properties that are stored as a
     * boolean column rather than a mask.
     */
    public function maskBit(): ?int
    {
        if ($this === self::Aura || $this === self::Debuff) {
            return null;
        }

        [$names, $prefix] = match (true) {
            $this->isCounter()        => [Spell::ALL_COUNTERS, 'counter_'],
            $this->isImmunityBypass() => [Spell::ALL_IMMUNITIES, 'bypass_'],
            default                   => [Spell::ALL_MISS_TYPES, 'miss_'],
        };

        foreach ($names as $bit => $name) {
            if ($this->value === sprintf('%s%s', $prefix, $name)) {
                return (int)$bit;
            }
        }

        throw new LogicException(sprintf('No bit found for SpellProperty: %s', $this->value));
    }

    public function isCounter(): bool
    {
        return str_starts_with($this->value, 'counter_');
    }

    /**
     * Whether this property records that the spell lands despite an active player immunity.
     */
    public function isImmunityBypass(): bool
    {
        return str_starts_with($this->value, 'bypass_');
    }
}
