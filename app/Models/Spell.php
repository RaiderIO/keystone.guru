<?php

namespace App\Models;

use Eloquent;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\UrlGenerator;

/**
 * @property int $id
 * @property string $icon_name
 * @property string $name
 * @property string $dispel_type
 * @property int $schools_mask
 * @property boolean $aura
 *
 * @property string $icon_url
 *
 * @mixin Eloquent
 */
class Spell extends CacheModel
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

    public $incrementing = false;
    public $timestamps = false;

    public $hidden = ['pivot'];
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
}
