<?php

namespace App\Models;

/**
 * @property string $name
 * @property \Illuminate\Support\Collection $specializations
 */
class CharacterClass extends IconFileModel
{
    public $timestamps = false;
    public $hidden = ['icon_file_id', 'pivot'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['key'];

    /**
     * @return string The key as used in the front-end to identify the dungeon.
     */
    public function getKeyAttribute(){
        return strtolower(str_replace(" ", "", $this->name));
    }

    function specializations()
    {
        return $this->hasMany('App\Models\CharacterSpecialization');
    }

    function races()
    {
        return $this->belongsToMany('App\Models\CharacterRace', 'character_race_class_couplings');
    }

    function dungeonrouteplayerclasses()
    {
        return $this->belongsToMany('App\Models\DungeonRoutePlayerClass', 'dungeon_route_player_classes');
    }
}
