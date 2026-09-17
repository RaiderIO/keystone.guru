<?php

namespace App\Models\Spell;

use App\Models\Traits\BitmaskEnum;

/**
 * The player abilities a spell has been seen to be countered by, one bit each on `spells`.`counters_mask`.
 */
enum SpellCounter: int
{
    use BitmaskEnum;

    case Vanish         = 1;
    case Shadowmeld     = 2;
    case FeignDeath     = 4;
    case Invisibility   = 8;
    case CloakOfShadows = 16;

    public const string TRANSLATION_KEY_PREFIX = 'spellcounters.';

    /**
     * The slug of this counter, which doubles as the suffix of its `spellcounters.*` translation key.
     */
    public function slug(): string
    {
        return match ($this) {
            self::Vanish         => 'vanish',
            self::Shadowmeld     => 'shadowmeld',
            self::FeignDeath     => 'feign_death',
            self::Invisibility   => 'invisibility',
            self::CloakOfShadows => 'cloak_of_shadows',
        };
    }
}
