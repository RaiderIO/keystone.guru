<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property string $name
 * @property int $faction_id
 * @property Faction $faction
 * @property Collection $classes
 * @property Collection $specializations
 *
 * @mixin Eloquent
 */
class CharacterRace extends CacheModel
{
    public $timestamps = false;
    public $hidden = ['icon_file_id', 'pivot'];

    /**
     * @return BelongsToMany
     */
    function classes()
    {
        return $this->belongsToMany('App\Models\CharacterClass', 'character_race_class_couplings');
    }

    /**
     * @return HasMany
     */
    function specializations()
    {
        return $this->hasMany('App\Models\CharacterClass');
    }

    /**
     * @return BelongsTo
     */
    public function faction()
    {
        return $this->belongsTo('App\Models\Faction');
    }

    /**
     * @return HasMany
     */
    function dungeonrouteplayerrace()
    {
        return $this->hasMany('App\Models\DungeonRoutePlayerRace');
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
