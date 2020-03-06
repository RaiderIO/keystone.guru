<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $key
 * @property boolean $admin_only
 *
 * @mixin \Eloquent
 */
class MapIconType extends Model
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
