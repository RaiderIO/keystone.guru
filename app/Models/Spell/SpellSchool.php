<?php

namespace App\Models\Spell;

use App\Models\Traits\BitmaskEnum;

/**
 * The damage schools a spell deals, one bit each on `spells`.`schools_mask`.
 *
 * The backing value is the bit as the combat log reports it, so a school mask parsed from a log line can be
 * compared against it directly.
 */
enum SpellSchool: int
{
    use BitmaskEnum;

    case Physical = 1;
    case Holy     = 2;
    case Fire     = 4;
    case Nature   = 8;
    case Frost    = 16;
    case Shadow   = 32;
    case Arcane   = 64;

    public const string TRANSLATION_KEY_PREFIX = 'spellschools.';

    /** Every non-physical school - what a magic-only immunity (Anti-Magic Shell, Blessing of Spellwarding) protects against. */
    public const int MASK_MAGIC = self::Holy->value | self::Fire->value | self::Nature->value |
        self::Frost->value | self::Shadow->value | self::Arcane->value;

    /** Every school - what a full immunity (Divine Shield, Ice Block) protects against. */
    public const int MASK_ALL = self::Physical->value | self::MASK_MAGIC;

    /**
     * The slug of this school, which doubles as the suffix of its `spellschools.*` translation key.
     */
    public function slug(): string
    {
        return match ($this) {
            self::Physical => 'physical',
            self::Holy     => 'holy',
            self::Fire     => 'fire',
            self::Nature   => 'nature',
            self::Frost    => 'frost',
            self::Shadow   => 'shadow',
            self::Arcane   => 'arcane',
        };
    }
}
