<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $type
 *
 * @mixin Eloquent
 */
class NpcType extends CacheModel
{
    const ABERRATION    = 1;
    const BEAST         = 2;
    const CRITTER       = 3;
    const DEMON         = 4;
    const DRAGONKIN     = 5;
    const ELEMENTAL     = 6;
    const GIANT         = 7;
    const HUMANOID      = 8;
    const MECHANICAL    = 9;
    const UNDEAD        = 10;
    const UNCATEGORIZED = 11;

    const ALL = [
        'Aberration'    => self::ABERRATION,
        'Beast'         => self::BEAST,
        'Critter'       => self::CRITTER,
        'Demon'         => self::DEMON,
        'Dragonkin'     => self::DRAGONKIN,
        'Elemental'     => self::ELEMENTAL,
        'Giant'         => self::GIANT,
        'Humanoid'      => self::HUMANOID,
        'Mechanical'    => self::MECHANICAL,
        'Undead'        => self::UNDEAD,
        'Uncategorized' => self::UNCATEGORIZED,
    ];

    protected $fillable = ['id', 'type'];
    public $timestamps = false;

    public function getTypeKeyAttribute()
    {
        return strtolower($this->type);
    }

    /**
     * Gets all derived NPCs from this type.
     *
     * @return HasMany
     */
    function npcs()
    {
        return $this->hasMany('App\Models\Npc');
    }
}
