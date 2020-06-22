<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $key
 * @property int $width
 * @property int $height
 * @property boolean $admin_only
 *
 * @property \App\Models\MapIcon $mapicons
 *
 * @mixin \Eloquent
 */
class MapIconType extends Model
{
    public $timestamps = false;

    public function mapicons()
    {
        return $this->hasMany('App\Models\MapIcon');
    }

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
