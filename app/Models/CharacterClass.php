<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property \Illuminate\Support\Collection $specializations
 */
class CharacterClass extends Model
{
    public $hidden = ['created_at', 'updated_at'];

    function specializations()
    {
        return $this->hasMany('App\Models\CharacterSpecialization');
    }
}
