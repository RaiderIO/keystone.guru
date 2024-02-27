<?php

namespace App\Models;

use App\Models\Mapping\MappingModelInterface;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\UrlGenerator;

/**
 * @property int    $id
 * @property string $category
 * @property string $icon_name
 * @property string $name
 * @property string $dispel_type
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
     * @return Application|UrlGenerator|string
     */
    public function getIconUrlAttribute()
    {
        return url(sprintf('/images/spells/%s.png', $this->icon_name));
    }

    public function getDungeonId(): ?int
    {
        // Spells aren't tied to a specific dungeon, but they're part of the mapping
        return 0;
    }
}
