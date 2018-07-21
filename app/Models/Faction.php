<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property \Illuminate\Support\Collection $races
 * @property \Illuminate\Support\Collection $dungeonroutes
 */
class Faction extends Model
{
    public $timestamps = false;
    public $hidden = ['icon_file_id', 'pivot'];

    function iconfile()
    {
        return $this->hasOne('App\Models\File', 'model_id')->where('model_class', '=', get_class($this));
    }

    function races()
    {
        return $this->hasMany('App\Models\CharacterRace');
    }

    function dungeonroutes()
    {
        return $this->hasMany('App\Models\DungeonRoute');
    }
}
