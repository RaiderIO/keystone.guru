<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $character_race_id
 * @property int $character_class_id
 * @property \Illuminate\Support\Collection $specializations
 */
class CharacterRaceClassCoupling extends Model
{
    public $timestamps = false;

    public static function boot()
    {
        parent::boot();

        // This model may NOT be deleted, it's read only!
        static::deleting(function ($someModel) {
            return false;
        });
    }
}
