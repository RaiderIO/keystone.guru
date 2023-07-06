<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $category
 * @property string $name
 * @property string $description
 *
 * @mixin Eloquent
 */
class RouteAttribute extends Model
{
    public $timestamps = false;

    public $hidden = ['id', 'pivot'];

    public static function boot()
    {
        parent::boot();

        // This model may NOT be deleted, it's read only!
        static::deleting(function ($someModel) {
            return false;
        });
    }
}
