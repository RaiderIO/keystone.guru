<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property \Illuminate\Support\Collection $specializations
 */
class CharacterRace extends Model
{
    public $timestamps = false;

    function specializations()
    {
        return $this->hasMany('App\Models\CharacterClass');
    }

    function classes()
    {
        return $this->belongsToMany('App\Models\CharacterClass', 'character_race_class_couplings');
    }
}
