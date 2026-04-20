<?php

namespace App\Models\Spell;

use App\Logic\CombatLog\Guid\MissType\Absorb;
use App\Logic\CombatLog\Guid\MissType\Block;
use App\Logic\CombatLog\Guid\MissType\Deflect;
use App\Logic\CombatLog\Guid\MissType\Dodge;
use App\Logic\CombatLog\Guid\MissType\Evade;
use App\Logic\CombatLog\Guid\MissType\Immune;
use App\Logic\CombatLog\Guid\MissType\Miss;
use App\Logic\CombatLog\Guid\MissType\Parry;
use App\Logic\CombatLog\Guid\MissType\Reflect;
use App\Logic\CombatLog\Guid\MissType\Resist;

trait SpellConstants
{
    public const int SCHOOL_PHYSICAL = 1;
    public const int SCHOOL_HOLY     = 2;
    public const int SCHOOL_FIRE     = 4;
    public const int SCHOOL_NATURE   = 8;
    public const int SCHOOL_FROST    = 16;
    public const int SCHOOL_SHADOW   = 32;
    public const int SCHOOL_ARCANE   = 64;

    public const array ALL_SCHOOLS = [
        self::SCHOOL_PHYSICAL => 'physical',
        self::SCHOOL_HOLY     => 'holy',
        self::SCHOOL_FIRE     => 'fire',
        self::SCHOOL_NATURE   => 'nature',
        self::SCHOOL_FROST    => 'frost',
        self::SCHOOL_SHADOW   => 'shadow',
        self::SCHOOL_ARCANE   => 'arcane',
    ];

    public const int MISS_TYPE_ABSORB  = 1;
    public const int MISS_TYPE_BLOCK   = 2;
    public const int MISS_TYPE_DEFLECT = 4;
    public const int MISS_TYPE_DODGE   = 8;
    public const int MISS_TYPE_EVADE   = 16;
    public const int MISS_TYPE_IMMUNE  = 32;
    public const int MISS_TYPE_MISS    = 64;
    public const int MISS_TYPE_PARRY   = 128;
    public const int MISS_TYPE_REFLECT = 256;
    public const int MISS_TYPE_RESIST  = 512;

    public const array ALL_MISS_TYPES = [
        self::MISS_TYPE_ABSORB  => 'absorb',
        self::MISS_TYPE_BLOCK   => 'block',
        self::MISS_TYPE_DEFLECT => 'deflect',
        self::MISS_TYPE_DODGE   => 'dodge',
        self::MISS_TYPE_EVADE   => 'evade',
        self::MISS_TYPE_IMMUNE  => 'immune',
        self::MISS_TYPE_MISS    => 'miss',
        self::MISS_TYPE_PARRY   => 'parry',
        self::MISS_TYPE_REFLECT => 'reflect',
        self::MISS_TYPE_RESIST  => 'resist',
    ];

    public const array GUID_MISS_TYPE_MAPPING = [
        Absorb::class  => self::MISS_TYPE_ABSORB,
        Block::class   => self::MISS_TYPE_BLOCK,
        Deflect::class => self::MISS_TYPE_DEFLECT,
        Dodge::class   => self::MISS_TYPE_DODGE,
        Evade::class   => self::MISS_TYPE_EVADE,
        Immune::class  => self::MISS_TYPE_IMMUNE,
        Miss::class    => self::MISS_TYPE_MISS,
        Parry::class   => self::MISS_TYPE_PARRY,
        Reflect::class => self::MISS_TYPE_REFLECT,
        Resist::class  => self::MISS_TYPE_RESIST,
    ];

    public const string DISPEL_TYPE_MAGIC         = 'magic';
    public const string DISPEL_TYPE_DISEASE       = 'disease';
    public const string DISPEL_TYPE_POISON        = 'poison';
    public const string DISPEL_TYPE_CURSE         = 'curse';
    public const string DISPEL_TYPE_ENRAGE        = 'enrage';
    public const string DISPEL_TYPE_NONE          = 'none';
    public const string DISPEL_TYPE_NOT_AVAILABLE = 'n_a';
    public const string DISPEL_TYPE_UNKNOWN       = 'unknown';

    public const array ALL_DISPEL_TYPES = [
        self::DISPEL_TYPE_MAGIC,
        self::DISPEL_TYPE_DISEASE,
        self::DISPEL_TYPE_POISON,
        self::DISPEL_TYPE_CURSE,
        self::DISPEL_TYPE_ENRAGE,
        self::DISPEL_TYPE_NONE,
        self::DISPEL_TYPE_NOT_AVAILABLE,
        self::DISPEL_TYPE_UNKNOWN,
    ];

    public const string CATEGORY_GENERAL      = 'general';
    public const string CATEGORY_WARRIOR      = 'warrior';
    public const string CATEGORY_HUNTER       = 'hunter';
    public const string CATEGORY_DEATH_KNIGHT = 'death_knight';
    public const string CATEGORY_MAGE         = 'mage';
    public const string CATEGORY_PRIEST       = 'priest';
    public const string CATEGORY_MONK         = 'monk';
    public const string CATEGORY_ROGUE        = 'rogue';
    public const string CATEGORY_WARLOCK      = 'warlock';
    public const string CATEGORY_SHAMAN       = 'shaman';
    public const string CATEGORY_PALADIN      = 'paladin';
    public const string CATEGORY_DRUID        = 'druid';
    public const string CATEGORY_DEMON_HUNTER = 'demon_hunter';
    public const string CATEGORY_EVOKER       = 'evoker';
    public const string CATEGORY_UNKNOWN      = 'unknown';

    public const array ALL_CATEGORIES = [
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

    public const string COOLDOWN_GROUP_ALL            = 'all';
    public const string COOLDOWN_GROUP_CD_EXTERNAL    = 'cd_external';
    public const string COOLDOWN_GROUP_CD_GROUP       = 'cd_group';
    public const string COOLDOWN_GROUP_CD_PERSONAL    = 'cd_personal';
    public const string COOLDOWN_GROUP_DR_EXTERNAL    = 'dr_external';
    public const string COOLDOWN_GROUP_DR_GROUP       = 'dr_group';
    public const string COOLDOWN_GROUP_DR_PERSONAL    = 'dr_personal';
    public const string COOLDOWN_GROUP_GROUP_DR       = 'group_dr';
    public const string COOLDOWN_GROUP_GROUP_HEAL_DPS = 'group_heal_dps';
    public const string COOLDOWN_GROUP_IMMUNITY       = 'immunity';
    public const string COOLDOWN_GROUP_MOVEMENT       = 'movement';
    public const string COOLDOWN_GROUP_PERSONAL       = 'personal';
    public const string COOLDOWN_GROUP_PERSONAL_CD    = 'personal_cd';
    public const string COOLDOWN_GROUP_UTILITY        = 'utility';
    public const string COOLDOWN_GROUP_UNKNOWN        = 'unknown';

    public const array ALL_COOLDOWN_GROUPS = [
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

    public const string MECHANIC_ASLEEP        = 'asleep';
    public const string MECHANIC_BANISHED      = 'banished';
    public const string MECHANIC_BLEEDING      = 'bleeding';
    public const string MECHANIC_CHARMED       = 'charmed';
    public const string MECHANIC_GRIPPED       = 'gripped';
    public const string MECHANIC_DAZED         = 'dazed';
    public const string MECHANIC_DISARMED      = 'disarmed';
    public const string MECHANIC_DISCOVERY     = 'discovery';
    public const string MECHANIC_DISORIENTED   = 'disoriented';
    public const string MECHANIC_DISTRACTED    = 'distracted';
    public const string MECHANIC_ENRAGED       = 'enraged';
    public const string MECHANIC_SNARED        = 'snared';
    public const string MECHANIC_FLEEING       = 'fleeing';
    public const string MECHANIC_FROZEN        = 'frozen';
    public const string MECHANIC_HEALING       = 'healing';
    public const string MECHANIC_HORRIFIED     = 'horrified';
    public const string MECHANIC_INCAPACITATED = 'incapacitated';
    public const string MECHANIC_INTERRUPTED   = 'interrupted';
    public const string MECHANIC_INVULNERABLE  = 'invulnerable';
    public const string MECHANIC_MOUNTED       = 'mounted';
    public const string MECHANIC_SLOWED        = 'slowed';
    public const string MECHANIC_POLYMORPHED   = 'polymorphed';
    public const string MECHANIC_ROOTED        = 'rooted';
    public const string MECHANIC_SAPPED        = 'sapped';
    public const string MECHANIC_INFECTED      = 'infected';
    public const string MECHANIC_SHACKLED      = 'shackled';
    public const string MECHANIC_SHIELDED      = 'shielded';
    public const string MECHANIC_SILENCED      = 'silenced';
    public const string MECHANIC_STUNNED       = 'stunned';
    public const string MECHANIC_TURNED        = 'turned';
    public const string MECHANIC_WOUNDED       = 'wounded';

    // IDs are wonky because they're Blizzard IDs
    public const array ALL_MECHANIC = [
        self::MECHANIC_ASLEEP        => 10,
        self::MECHANIC_BANISHED      => 18,
        self::MECHANIC_BLEEDING      => 15,
        self::MECHANIC_CHARMED       => 1,
        self::MECHANIC_GRIPPED       => 6,
        self::MECHANIC_DAZED         => 27,
        self::MECHANIC_DISARMED      => 3,
        self::MECHANIC_DISCOVERY     => 28,
        self::MECHANIC_DISORIENTED   => 2,
        self::MECHANIC_DISTRACTED    => 4,
        self::MECHANIC_ENRAGED       => 31,
        self::MECHANIC_SNARED        => 11,
        self::MECHANIC_FLEEING       => 5,
        self::MECHANIC_FROZEN        => 13,
        self::MECHANIC_HEALING       => 16,
        self::MECHANIC_HORRIFIED     => 24,
        self::MECHANIC_INCAPACITATED => 14,
        self::MECHANIC_INTERRUPTED   => 26,
        self::MECHANIC_INVULNERABLE  => 29,
        self::MECHANIC_MOUNTED       => 21,
        self::MECHANIC_SLOWED        => 8,
        self::MECHANIC_POLYMORPHED   => 17,
        self::MECHANIC_ROOTED        => 7,
        self::MECHANIC_SAPPED        => 30,
        self::MECHANIC_INFECTED      => 22,
        self::MECHANIC_SHACKLED      => 20,
        self::MECHANIC_SHIELDED      => 19,
        self::MECHANIC_SILENCED      => 9,
        self::MECHANIC_STUNNED       => 12,
        self::MECHANIC_TURNED        => 23,
        self::MECHANIC_WOUNDED       => 32,
    ];

    // Some hard coded spells that we have exceptions for in the code
    public const int SPELL_BLOODLUST             = 2825;
    public const int SPELL_HEROISM               = 32182;
    public const int SPELL_TIME_WARP             = 80353;
    public const int SPELL_FURY_OF_THE_ASPECTS   = 390386;
    public const int SPELL_ANCIENT_HYSTERIA      = 90355;
    public const int SPELL_PRIMAL_RAGE           = 264667;
    public const int SPELL_FERAL_HIDE_DRUMS      = 381301;
    public const int SPELL_THUNDEROUS_DRUMS      = 444257;
    public const int SPELL_HARRIERS_CRY          = 466904;
    public const int SPELL_SHROUD_OF_CONCEALMENT = 114018;

    public const array BLOODLUSTY_SPELLS = [
        self::SPELL_BLOODLUST,
        self::SPELL_HEROISM,
        self::SPELL_TIME_WARP,
        self::SPELL_FURY_OF_THE_ASPECTS,
        self::SPELL_ANCIENT_HYSTERIA,
        self::SPELL_PRIMAL_RAGE,
        self::SPELL_FERAL_HIDE_DRUMS,
        self::SPELL_THUNDEROUS_DRUMS,
        self::SPELL_HARRIERS_CRY,
    ];

    public const array EXCLUDE_MDT_IMPORT_SPELLS = [
        186439,
        // Shadow Mend, was removed from the game
    ];
}
