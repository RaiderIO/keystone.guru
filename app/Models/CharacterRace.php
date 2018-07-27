<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property int $faction_id
 * @property \App\Models\Faction $faction
 * @property \Illuminate\Support\Collection $classes
 * @property \Illuminate\Support\Collection $specializations
 */
class CharacterRace extends Model
{
    public $timestamps = false;
    public $hidden = ['icon_file_id', 'pivot'];

    function classes()
    {
        return $this->belongsToMany('App\Models\CharacterClass', 'character_race_class_couplings');
    }

    function specializations()
    {
        return $this->hasMany('App\Models\CharacterClass');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function faction()
    {
        return $this->belongsTo('App\Models\Faction');
    }

    function dungeonrouteplayerrace()
    {
        return $this->hasMany('App\Models\DungeonRoutePlayerRace');
    }
}
