<?php

namespace App\Models;

use Eloquent;

/**
 * @property int $id
 * @property string $name
 *
 * @mixin Eloquent
 */
class RaidMarker extends CacheModel
{
    public $timestamps = false;

    public static function boot()
    {
        parent::boot();

        // This model may NOT be deleted, it's read only!
        static::deleting(function ($someModel)
        {
            return false;
        });
    }
}
