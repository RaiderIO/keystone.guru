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
 * @property Collection $races
 * @property Collection $dungeonroutes
 *
 * @mixin Eloquent
 */
class Faction extends CacheModel
{
    use HasIconFile;

    const FACTION_UNSPECIFIED = 'unspecified';
    const FACTION_HORDE       = 'horde';
    const FACTION_ALLIANCE    = 'alliance';

    public $timestamps = false;
    public $hidden = ['icon_file_id', 'pivot'];
    public $fillable = ['icon_file_id', 'key', 'name', 'color'];


    /**
     * @return HasMany
     */
    function races()
    {
        return $this->hasMany('App\Models\CharacterRace');
    }

    /**
     * @return HasMany
     */
    function dungeonroutes()
    {
        return $this->hasMany('App\Models\DungeonRoute');
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
