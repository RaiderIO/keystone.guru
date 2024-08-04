<?php

namespace App\Models\Spell;

trait SpellConstants
{
    public const SCHOOL_PHYSICAL = 1;
    public const SCHOOL_HOLY     = 2;
    public const SCHOOL_FIRE     = 4;
    public const SCHOOL_NATURE   = 8;
    public const SCHOOL_FROST    = 16;
    public const SCHOOL_SHADOW   = 32;
    public const SCHOOL_ARCANE   = 64;

    public const ALL_SCHOOLS = [
        'Physical' => self::SCHOOL_PHYSICAL,
        'Holy'     => self::SCHOOL_HOLY,
        'Fire'     => self::SCHOOL_FIRE,
        'Nature'   => self::SCHOOL_NATURE,
        'Frost'    => self::SCHOOL_FROST,
        'Shadow'   => self::SCHOOL_SHADOW,
        'Arcane'   => self::SCHOOL_ARCANE,
    ];

    public const DISPEL_TYPE_MAGIC         = 'Magic';
    public const DISPEL_TYPE_DISEASE       = 'Disease';
    public const DISPEL_TYPE_POISON        = 'Poison';
    public const DISPEL_TYPE_CURSE         = 'Curse';
    public const DISPEL_TYPE_ENRAGE        = 'Enrage';
    public const DISPEL_TYPE_NOT_AVAILABLE = 'N/A';
    public const DISPEL_TYPE_UNKNOWN       = 'Unknown';

    public const ALL_DISPEL_TYPES = [
        self::DISPEL_TYPE_MAGIC,
        self::DISPEL_TYPE_DISEASE,
        self::DISPEL_TYPE_POISON,
        self::DISPEL_TYPE_CURSE,
        self::DISPEL_TYPE_ENRAGE,
        self::DISPEL_TYPE_NOT_AVAILABLE,
        self::DISPEL_TYPE_UNKNOWN,
    ];

    public const CATEGORY_GENERAL      = 'general';
    public const CATEGORY_WARRIOR      = 'warrior';
    public const CATEGORY_HUNTER       = 'hunter';
    public const CATEGORY_DEATH_KNIGHT = 'death_knight';
    public const CATEGORY_MAGE         = 'mage';
    public const CATEGORY_PRIEST       = 'priest';
    public const CATEGORY_MONK         = 'monk';
    public const CATEGORY_ROGUE        = 'rogue';
    public const CATEGORY_WARLOCK      = 'warlock';
    public const CATEGORY_SHAMAN       = 'shaman';
    public const CATEGORY_PALADIN      = 'paladin';
    public const CATEGORY_DRUID        = 'druid';
    public const CATEGORY_DEMON_HUNTER = 'demon_hunter';
    public const CATEGORY_EVOKER       = 'evoker';
    public const CATEGORY_UNKNOWN      = 'unknown';

    public const ALL_CATEGORIES = [
        self::CATEGORY_GENERAL,
        self::CATEGORY_WARRIOR,
        self::CATEGORY_HUNTER,
        self::CATEGORY_DEATH_KNIGHT,
        self::CATEGORY_MAGE,
        self::CATEGORY_PRIEST,
        self::CATEGORY_MONK,
        self::CATEGORY_ROGUE,
        self::CATEGORY_WARLOCK,
        self::CATEGORY_SHAMAN,
        self::CATEGORY_PALADIN,
        self::CATEGORY_DRUID,
        self::CATEGORY_DEMON_HUNTER,
        self::CATEGORY_EVOKER,
        self::CATEGORY_UNKNOWN,
    ];

    public const COOLDOWN_GROUP_ALL            = 'all';
    public const COOLDOWN_GROUP_CD_EXTERNAL    = 'cd_external';
    public const COOLDOWN_GROUP_CD_GROUP       = 'cd_group';
    public const COOLDOWN_GROUP_CD_PERSONAL    = 'cd_personal';
    public const COOLDOWN_GROUP_DR_EXTERNAL    = 'dr_external';
    public const COOLDOWN_GROUP_DR_GROUP       = 'dr_group';
    public const COOLDOWN_GROUP_DR_PERSONAL    = 'dr_personal';
    public const COOLDOWN_GROUP_GROUP_DR       = 'group_dr';
    public const COOLDOWN_GROUP_GROUP_HEAL_DPS = 'group_heal_dps';
    public const COOLDOWN_GROUP_IMMUNITY       = 'immunity';
    public const COOLDOWN_GROUP_MOVEMENT       = 'movement';
    public const COOLDOWN_GROUP_PERSONAL       = 'personal';
    public const COOLDOWN_GROUP_PERSONAL_CD    = 'personal_cd';
    public const COOLDOWN_GROUP_UTILITY        = 'utility';
    public const COOLDOWN_GROUP_UNKNOWN        = 'unknown';

    public const ALL_COOLDOWN_GROUPS = [
        self::COOLDOWN_GROUP_ALL,
        self::COOLDOWN_GROUP_CD_EXTERNAL,
        self::COOLDOWN_GROUP_CD_GROUP,
        self::COOLDOWN_GROUP_CD_PERSONAL,
        self::COOLDOWN_GROUP_DR_EXTERNAL,
        self::COOLDOWN_GROUP_DR_GROUP,
        self::COOLDOWN_GROUP_DR_PERSONAL,
        self::COOLDOWN_GROUP_GROUP_DR,
        self::COOLDOWN_GROUP_GROUP_HEAL_DPS,
        self::COOLDOWN_GROUP_IMMUNITY,
        self::COOLDOWN_GROUP_MOVEMENT,
        self::COOLDOWN_GROUP_PERSONAL,
        self::COOLDOWN_GROUP_PERSONAL_CD,
        self::COOLDOWN_GROUP_UTILITY,
        self::COOLDOWN_GROUP_UNKNOWN,
    ];

    // Some hard coded spells that we have exceptions for in the code
    public const SPELL_BLOODLUST           = 2825;
    public const SPELL_HEROISM             = 32182;
    public const SPELL_TIME_WARP           = 80353;
    public const SPELL_FURY_OF_THE_ASPECTS = 390386;
    public const SPELL_ANCIENT_HYSTERIA    = 90355;
    public const SPELL_PRIMAL_RAGE         = 264667;
    public const SPELL_FERAL_HIDE_DRUMS    = 381301;

    public const MECHANIC_ASLEEP        = 'asleep';
    public const MECHANIC_BANISHED      = 'banished';
    public const MECHANIC_BLEEDING      = 'bleeding';
    public const MECHANIC_CHARMED       = 'charmed';
    public const MECHANIC_GRIPPED       = 'gripped';
    public const MECHANIC_DAZED         = 'dazed';
    public const MECHANIC_DISARMED      = 'disarmed';
    public const MECHANIC_DISCOVERY     = 'discovery';
    public const MECHANIC_DISORIENTED   = 'disoriented';
    public const MECHANIC_DISTRACTED    = 'distracted';
    public const MECHANIC_ENRAGED       = 'enraged';
    public const MECHANIC_SNARED        = 'snared';
    public const MECHANIC_FLEEING       = 'fleeing';
    public const MECHANIC_FROZEN        = 'frozen';
    public const MECHANIC_HEALING       = 'healing';
    public const MECHANIC_HORRIFIED     = 'horrified';
    public const MECHANIC_INCAPACITATED = 'incapacitated';
    public const MECHANIC_INTERRUPTED   = 'interrupted';
    public const MECHANIC_INVULNERABLE  = 'invulnerable';
    public const MECHANIC_MOUNTED       = 'mounted';
    public const MECHANIC_SLOWED        = 'slowed';
    public const MECHANIC_POLYMORPHED   = 'polymorphed';
    public const MECHANIC_ROOTED        = 'rooted';
    public const MECHANIC_SAPPED        = 'sapped';
    public const MECHANIC_INFECTED      = 'infected';
    public const MECHANIC_SHACKLED      = 'shackled';
    public const MECHANIC_SHIELDED      = 'shielded';
    public const MECHANIC_SILENCED      = 'silenced';
    public const MECHANIC_STUNNED       = 'stunned';
    public const MECHANIC_TURNED        = 'turned';
    public const MECHANIC_WOUNDED       = 'wounded';

    public const ALL_MECHANIC = [
        self::MECHANIC_ASLEEP,
        self::MECHANIC_BANISHED,
        self::MECHANIC_BLEEDING,
        self::MECHANIC_CHARMED,
        self::MECHANIC_GRIPPED,
        self::MECHANIC_DAZED,
        self::MECHANIC_DISARMED,
        self::MECHANIC_DISCOVERY,
        self::MECHANIC_DISORIENTED,
        self::MECHANIC_DISTRACTED,
        self::MECHANIC_ENRAGED,
        self::MECHANIC_SNARED,
        self::MECHANIC_FLEEING,
        self::MECHANIC_FROZEN,
        self::MECHANIC_HEALING,
        self::MECHANIC_HORRIFIED,
        self::MECHANIC_INCAPACITATED,
        self::MECHANIC_INTERRUPTED,
        self::MECHANIC_INVULNERABLE,
        self::MECHANIC_MOUNTED,
        self::MECHANIC_SLOWED,
        self::MECHANIC_POLYMORPHED,
        self::MECHANIC_ROOTED,
        self::MECHANIC_SAPPED,
        self::MECHANIC_INFECTED,
        self::MECHANIC_SHACKLED,
        self::MECHANIC_SHIELDED,
        self::MECHANIC_SILENCED,
        self::MECHANIC_STUNNED,
        self::MECHANIC_TURNED,
        self::MECHANIC_WOUNDED,
    ];
}
