<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property \Illuminate\Support\Collection $specializations
 */
class CharacterRace extends Model
{
    function specializations()
    {
        return $this->hasMany('App\Models\CharacterClass');
    }

    function class()
    {
        return $this->belongsToMany('App\Models\CharacterClass', 'character_race_class_couplings');
    }
}
