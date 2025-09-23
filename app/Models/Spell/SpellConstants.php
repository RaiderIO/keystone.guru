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

    public const MISS_TYPE_ABSORB  = 1;
    public const MISS_TYPE_BLOCK   = 2;
    public const MISS_TYPE_DEFLECT = 4;
    public const MISS_TYPE_DODGE   = 8;
    public const MISS_TYPE_EVADE   = 16;
    public const MISS_TYPE_IMMUNE  = 32;
    public const MISS_TYPE_MISS    = 64;
    public const MISS_TYPE_PARRY   = 128;
    public const MISS_TYPE_REFLECT = 256;
    public const MISS_TYPE_RESIST  = 512;

    public const ALL_MISS_TYPES = [
        'Absorb'  => self::MISS_TYPE_ABSORB,
        'Block'   => self::MISS_TYPE_BLOCK,
        'Deflect' => self::MISS_TYPE_DEFLECT,
        'Dodge'   => self::MISS_TYPE_DODGE,
        'Evade'   => self::MISS_TYPE_EVADE,
        'Immune'  => self::MISS_TYPE_IMMUNE,
        'Miss'    => self::MISS_TYPE_MISS,
        'Parry'   => self::MISS_TYPE_PARRY,
        'Reflect' => self::MISS_TYPE_REFLECT,
        'Resist'  => self::MISS_TYPE_RESIST,
    ];

    public const GUID_MISS_TYPE_MAPPING = [
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

    // IDs are wonky because they're Blizzard IDs
    public const ALL_MECHANIC = [
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
    public const SPELL_BLOODLUST             = 2825;
    public const SPELL_HEROISM               = 32182;
    public const SPELL_TIME_WARP             = 80353;
    public const SPELL_FURY_OF_THE_ASPECTS   = 390386;
    public const SPELL_ANCIENT_HYSTERIA      = 90355;
    public const SPELL_PRIMAL_RAGE           = 264667;
    public const SPELL_FERAL_HIDE_DRUMS      = 381301;
    public const SPELL_THUNDEROUS_DRUMS      = 444257;
    public const SPELL_HARRIERS_CRY          = 466904;
    public const SPELL_SHROUD_OF_CONCEALMENT = 114018;

    public const BLOODLUSTY_SPELLS = [
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

    public const EXCLUDE_MDT_IMPORT_SPELLS = [
        186439,
        // Shadow Mend, was removed from the game
    ];
}
