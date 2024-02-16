<?php

namespace App\Models;

use Eloquent;
use Illuminate\Support\Collection;

/**
 * @property int        $id
 * @property int        $character_race_id
 * @property int        $character_class_id
 * @property Collection $specializations
 *
 * @mixin Eloquent
 */
class CharacterRaceClassCoupling extends CacheModel
{
    public $timestamps = false;

    protected $fillable = [
        'character_race_id',
        'character_class_id',
    ];

    public static function boot()
    {
        parent::boot();

        // This model may NOT be deleted, it's read only!
        static::deleting(function ($someModel) {
            return false;
        });
    }
}
