<?php

namespace App\Models\Npc;

use App\Models\CacheModel;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int              $id
 * @property string           $type
 * @property Collection|Npc[] $npcs
 *
 * @mixin Eloquent
 */
class NpcType extends CacheModel
{
    use SeederModel;

    public const ABERRATION = 1;

    public const BEAST = 2;

    public const CRITTER = 3;

    public const DEMON = 4;

    public const DRAGONKIN = 5;

    public const ELEMENTAL = 6;

    public const GIANT = 7;

    public const HUMANOID = 8;

    public const MECHANICAL = 9;

    public const UNDEAD = 10;

    public const UNCATEGORIZED = 11;

    public const ALL = [
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

    public function getTypeKeyAttribute(): string
    {
        return strtolower($this->type);
    }

    /**
     * Gets all derived NPCs from this type.
     */
    public function npcs(): HasMany
    {
        return $this->hasMany(Npc::class);
    }
}
