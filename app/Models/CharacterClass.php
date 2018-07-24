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

    function specializations()
    {
        return $this->hasMany('App\Models\CharacterSpecialization');
    }

    function race()
    {
        return $this->belongsToMany('App\Models\CharacterRace', 'character_race_class_couplings');
    }
}
