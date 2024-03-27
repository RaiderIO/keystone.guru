<?php

namespace App\Models;

use App\Models\Mapping\MappingModelInterface;
use App\Models\Traits\SeederModel;
use Eloquent;

/**
 * @property int    $id
 * @property string $category
 * @property string $cooldown_group
 * @property string $dispel_type
 * @property string $icon_name
 * @property string $name
 * @property int    $schools_mask
 * @property bool   $aura
 * @property bool   $selectable
 * @property string $icon_url
 *
 * @mixin Eloquent
 */
class Spell extends CacheModel implements MappingModelInterface
{
    use SeederModel;

    public $incrementing = false;

    public $timestamps = false;

    public $hidden = ['pivot'];

    protected $appends = ['icon_url'];

    protected $fillable = [
        'id',
        'category',
        'cooldown_group',
        'dispel_type',
        'icon_name',
        'name',
        'schools_mask',
        'aura',
        'selectable',
        'icon_url',
    ];

    public const SCHOOL_PHYSICAL = 1;

    public const SCHOOL_HOLY = 2;

    public const SCHOOL_FIRE = 4;

    public const SCHOOL_NATURE = 8;

    public const SCHOOL_FROST = 16;

    public const SCHOOL_SHADOW = 32;

    public const SCHOOL_ARCANE = 64;

    public const ALL_SCHOOLS = [
        'Physical' => self::SCHOOL_PHYSICAL,
        'Holy'     => self::SCHOOL_HOLY,
        'Fire'     => self::SCHOOL_FIRE,
        'Nature'   => self::SCHOOL_NATURE,
        'Frost'    => self::SCHOOL_FROST,
        'Shadow'   => self::SCHOOL_SHADOW,
        'Arcane'   => self::SCHOOL_ARCANE,
    ];

    public const DISPEL_TYPE_MAGIC = 'Magic';

    public const DISPEL_TYPE_DISEASE = 'Disease';

    public const DISPEL_TYPE_POISON = 'Poison';

    public const DISPEL_TYPE_CURSE = 'Curse';

    public const ALL_DISPEL_TYPES = [
        self::DISPEL_TYPE_MAGIC,
        self::DISPEL_TYPE_DISEASE,
        self::DISPEL_TYPE_POISON,
        self::DISPEL_TYPE_CURSE,
    ];

    public const CATEGORY_GENERAL = 'general';

    public const CATEGORY_WARRIOR = 'warrior';

    public const CATEGORY_HUNTER = 'hunter';

    public const CATEGORY_DEATH_KNIGHT = 'death_knight';

    public const CATEGORY_MAGE = 'mage';

    public const CATEGORY_PRIEST = 'priest';

    public const CATEGORY_MONK = 'monk';

    public const CATEGORY_ROGUE = 'rogue';

    public const CATEGORY_WARLOCK = 'warlock';

    public const CATEGORY_SHAMAN = 'shaman';

    public const CATEGORY_PALADIN = 'paladin';

    public const CATEGORY_DRUID = 'druid';

    public const CATEGORY_DEMON_HUNTER = 'demon_hunter';

    public const CATEGORY_EVOKER = 'evoker';

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
    ];

    // Some hard coded spells that we have exceptions for in the code
    public const SPELL_BLOODLUST = 2825;

    public const SPELL_HEROISM = 32182;

    public const SPELL_TIME_WARP = 80353;

    public const SPELL_FURY_OF_THE_ASPECTS = 390386;

    public const SPELL_ANCIENT_HYSTERIA = 90355;

    public const SPELL_PRIMAL_RAGE = 264667;

    public const SPELL_FERAL_HIDE_DRUMS = 381301;

    public function getSchoolsAsArray(): array
    {
        $result = [];

        foreach (self::ALL_SCHOOLS as $school) {
            $result[$school] = $this->schools_mask & $school;
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getIconUrlAttribute(): string
    {
        return url(sprintf('/images/spells/%s.jpg', $this->icon_name));
    }

    public function getDungeonId(): ?int
    {
        // Spells aren't tied to a specific dungeon, but they're part of the mapping
        return 0;
    }
}
