<?php

namespace App\Models\Spell;

/**
 * The effect mechanic a spell applies. `spells`.`mechanic` stores it as the `spellmechanic.<value>` translation key.
 */
enum SpellMechanic: string
{
    case Asleep        = 'asleep';
    case Banished      = 'banished';
    case Bleeding      = 'bleeding';
    case Charmed       = 'charmed';
    case Gripped       = 'gripped';
    case Dazed         = 'dazed';
    case Disarmed      = 'disarmed';
    case Discovery     = 'discovery';
    case Disoriented   = 'disoriented';
    case Distracted    = 'distracted';
    case Enraged       = 'enraged';
    case Snared        = 'snared';
    case Fleeing       = 'fleeing';
    case Frozen        = 'frozen';
    case Healing       = 'healing';
    case Horrified     = 'horrified';
    case Incapacitated = 'incapacitated';
    case Interrupted   = 'interrupted';
    case Invulnerable  = 'invulnerable';
    case Mounted       = 'mounted';
    case Slowed        = 'slowed';
    case Polymorphed   = 'polymorphed';
    case Rooted        = 'rooted';
    case Sapped        = 'sapped';
    case Infected      = 'infected';
    case Shackled      = 'shackled';
    case Shielded      = 'shielded';
    case Silenced      = 'silenced';
    case Stunned       = 'stunned';
    case Turned        = 'turned';
    case Wounded       = 'wounded';

    public const string TRANSLATION_KEY_PREFIX = 'spellmechanic.';

    /**
     * This mechanic as `spells`.`mechanic` stores it, which is also its translation key.
     */
    public function translationKey(): string
    {
        return self::TRANSLATION_KEY_PREFIX . $this->value;
    }

    /**
     * The key for a mechanic slug that has not been matched to a case - Wowhead's mechanic names reach
     * `spells`.`mechanic` without ever being checked against the cases.
     */
    public static function translationKeyFor(string $mechanicSlug): string
    {
        return self::TRANSLATION_KEY_PREFIX . $mechanicSlug;
    }

    /**
     * Blizzard's ID for this mechanic, which does not follow the order of the cases.
     */
    public function blizzardId(): int
    {
        return match ($this) {
            self::Asleep        => 10,
            self::Banished      => 18,
            self::Bleeding      => 15,
            self::Charmed       => 1,
            self::Gripped       => 6,
            self::Dazed         => 27,
            self::Disarmed      => 3,
            self::Discovery     => 28,
            self::Disoriented   => 2,
            self::Distracted    => 4,
            self::Enraged       => 31,
            self::Snared        => 11,
            self::Fleeing       => 5,
            self::Frozen        => 13,
            self::Healing       => 16,
            self::Horrified     => 24,
            self::Incapacitated => 14,
            self::Interrupted   => 26,
            self::Invulnerable  => 29,
            self::Mounted       => 21,
            self::Slowed        => 8,
            self::Polymorphed   => 17,
            self::Rooted        => 7,
            self::Sapped        => 30,
            self::Infected      => 22,
            self::Shackled      => 20,
            self::Shielded      => 19,
            self::Silenced      => 9,
            self::Stunned       => 12,
            self::Turned        => 23,
            self::Wounded       => 32,
        };
    }
}
