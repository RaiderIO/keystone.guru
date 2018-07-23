<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property \Illuminate\Support\Collection $specializations
 */
class CharacterSpecialization extends Model
{
    public $timestamps = false;

    public $hidden = ['created_at', 'updated_at'];

    function class()
    {
        return $this->belongsTo('App\Models\CharacterClass');
    }
}
