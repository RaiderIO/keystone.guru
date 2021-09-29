<?php

namespace App\Models;

use App\Models\Traits\HasIconFile;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string $color
 * @property Collection $specializations
 *
 * @mixin Eloquent
 */
class CharacterClass extends CacheModel
{
    use HasIconFile;

    public $timestamps = false;
    public $hidden = ['icon_file_id', 'pivot'];
    public $fillable = ['key', 'name', 'color'];

    /**
     * @return HasMany
     */
    function specializations()
    {
        return $this->hasMany('App\Models\CharacterClassSpecialization');
    }

    /**
     * @return BelongsToMany
     */
    function races()
    {
        return $this->belongsToMany('App\Models\CharacterRace', 'character_race_class_couplings');
    }

    /**
     * @return BelongsToMany
     */
    function dungeonrouteplayerclasses()
    {
        return $this->belongsToMany('App\Models\DungeonRoutePlayerClass', 'dungeon_route_player_classes');
    }

    /**
     * @return BelongsToMany
     */
    function dungeonrouteplayerraces()
    {
        return $this->belongsToMany('App\Models\DungeonRoutePlayerRace', 'dungeon_route_player_races');
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
