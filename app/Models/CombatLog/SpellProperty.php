<?php

namespace App\Models\CombatLog;

use App\Models\Spell\Spell;

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
