<?php

namespace App\Models\Spell;

use App\Models\Traits\BitmaskEnum;

/**
 * The player immunities a spell has been seen to land through, one bit each on `spells`.`bypasses_immunities_mask`.
 */
enum SpellImmunity: int
{
    use BitmaskEnum;

    case DivineShield           = 1;
    case IceBlock               = 2;
    case AspectOfTheTurtle      = 4;
    case BlessingOfProtection   = 8;
    case BlessingOfSpellwarding = 16;
    case AntiMagicShell         = 32;

    /**
     * The slug of this immunity, which doubles as the suffix of its `spellimmunities.*` translation key.
     */
    public function slug(): string
    {
        return match ($this) {
            self::DivineShield           => 'divine_shield',
            self::IceBlock               => 'ice_block',
            self::AspectOfTheTurtle      => 'aspect_of_the_turtle',
            self::BlessingOfProtection   => 'blessing_of_protection',
            self::BlessingOfSpellwarding => 'blessing_of_spellwarding',
            self::AntiMagicShell         => 'anti_magic_shell',
        };
    }
}
