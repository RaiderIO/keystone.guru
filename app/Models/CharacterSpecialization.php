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

    public static function boot()
    {
        parent::boot();

        // This model may NOT be deleted, it's read only!
        static::deleting(function ($someModel) {
            return false;
        });
    }
}
