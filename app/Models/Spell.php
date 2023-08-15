<?php

namespace App\Models;

use App\Models\Mapping\MappingModelInterface;
use Eloquent;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\UrlGenerator;

/**
 * @property int     $id
 * @property string  $category
 * @property string  $icon_name
 * @property string  $name
 * @property string  $dispel_type
 * @property int     $schools_mask
 * @property boolean $aura
 * @property boolean $selectable
 *
 * @property string  $icon_url
 *
 * @mixin Eloquent
 */
class Spell extends CacheModel implements MappingModelInterface
{
    const SCHOOL_PHYSICAL = 1;
    const SCHOOL_HOLY     = 2;
    const SCHOOL_FIRE     = 4;
    const SCHOOL_NATURE   = 8;
    const SCHOOL_FROST    = 16;
    const SCHOOL_SHADOW   = 32;
    const SCHOOL_ARCANE   = 64;

    const ALL_SCHOOLS = [
        'Physical' => self::SCHOOL_PHYSICAL,
        'Holy'     => self::SCHOOL_HOLY,
        'Fire'     => self::SCHOOL_FIRE,
        'Nature'   => self::SCHOOL_NATURE,
        'Frost'    => self::SCHOOL_FROST,
        'Shadow'   => self::SCHOOL_SHADOW,
        'Arcane'   => self::SCHOOL_ARCANE,
    ];

    const DISPEL_TYPE_MAGIC   = 'Magic';
    const DISPEL_TYPE_DISEASE = 'Disease';
    const DISPEL_TYPE_POISON  = 'Poison';
    const DISPEL_TYPE_CURSE   = 'Curse';

    const ALL_DISPEL_TYPES = [
        self::DISPEL_TYPE_MAGIC,
        self::DISPEL_TYPE_DISEASE,
        self::DISPEL_TYPE_POISON,
        self::DISPEL_TYPE_CURSE,
    ];

    const CATEGORY_GENERAL      = 'general';
    const CATEGORY_WARRIOR      = 'warrior';
    const CATEGORY_HUNTER       = 'hunter';
    const CATEGORY_DEATH_KNIGHT = 'death_knight';
    const CATEGORY_MAGE         = 'mage';
    const CATEGORY_PRIEST       = 'priest';
    const CATEGORY_MONK         = 'monk';
    const CATEGORY_ROGUE        = 'rogue';
    const CATEGORY_WARLOCK      = 'warlock';
    const CATEGORY_SHAMAN       = 'shaman';
    const CATEGORY_PALADIN      = 'paladin';
    const CATEGORY_DRUID        = 'druid';
    const CATEGORY_DEMON_HUNTER = 'demon_hunter';
    const CATEGORY_EVOKER       = 'evoker';

    // Some hard coded spells that we have exceptions for in the code
    const SPELL_BLOODLUST           = 2825;
    const SPELL_HEROISM             = 32182;
    const SPELL_TIME_WARP           = 80353;
    const SPELL_FURY_OF_THE_ASPECTS = 390386;
    const SPELL_ANCIENT_HYSTERIA    = 90355;
    const SPELL_PRIMAL_RAGE         = 264667;
    const SPELL_FERAL_HIDE_DRUMS    = 381301;

    public $incrementing = false;
    public $timestamps   = false;

    public    $hidden  = ['pivot'];
    protected $appends = ['icon_url'];

    /**
     * @return array
     */
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

    /**
     * @return int|null
     */
    public function getDungeonId(): ?int
    {
        // Spells aren't tied to a specific dungeon, but they're part of the mapping
        return 0;
    }
}
