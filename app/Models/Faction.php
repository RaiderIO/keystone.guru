<?php

namespace App\Models;

use App\Models\Traits\HasIconFile;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $icon_file_id
 * @property string $key
 * @property string $name
 * @property string $color
 *
 * @property Collection|CharacterRace[] $races
 * @property Collection|DungeonRoute[] $dungeonroutes
 *
 * @mixin Eloquent
 */
class Faction extends CacheModel
{
    use HasIconFile;

    const FACTION_ANY         = 'any';
    const FACTION_UNSPECIFIED = 'unspecified';
    const FACTION_HORDE       = 'horde';
    const FACTION_ALLIANCE    = 'alliance';

    public $timestamps = false;
    public $hidden = ['icon_file_id', 'pivot'];
    public $fillable = ['id', 'icon_file_id', 'key', 'name', 'color'];

    const ALL = [
        self::FACTION_UNSPECIFIED => 1,
        self::FACTION_HORDE       => 2,
        self::FACTION_ALLIANCE    => 3,
    ];

    /**
     * @return HasMany
     */
    public function races(): HasMany
    {
        return $this->hasMany(CharacterRace::class);
    }

    /**
     * @return HasMany
     */
    public function dungeonroutes(): HasMany
    {
        return $this->hasMany(DungeonRoute::class);
    }

    public static function boot()
    {
        parent::boot();

        // This model may NOT be deleted, it's read only!
        static::deleting(function ($someModel) {
            return false;
        });
    }
}
