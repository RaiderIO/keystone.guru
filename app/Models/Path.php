<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int
 * @property $dungeon_route_id int
 * @property $floor_id int
 * @property $polyline_id int
 *
 * @property DungeonRoute $dungeonroute
 *
 * @property \App\Models\Polyline $polyline
 *
 * @mixin \Eloquent
 */
class Path extends Model
{
    public $visible = ['id', 'polyline'];
    public $with = ['polyline'];

    /**
     * Get the dungeon route that this route is attached to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function dungeonroute()
    {
        return $this->belongsTo('App\Models\DungeonRoute');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    function polyline()
    {
        return $this->hasOne('App\Models\Polyline', 'model_id')->where('model_class', get_class($this));
    }

    public static function boot()
    {
        parent::boot();

        // Delete Path properly if it gets deleted
        static::deleting(function ($item) {
            /** @var $item Path */
            if ($item->polyline !== null) {
                $item->polyline->delete();
            }
        });
    }
}
