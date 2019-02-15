<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int
 * @property $dungeon_route_id int
 * @property $floor_id int
 * @property $color string
 * @property $vertices_json string
 * @property $dungeonroute DungeonRoute
 */
class Path extends Model
{
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function polyline()
    {
        return $this->hasMany('App\Models\Polyline');
    }
}
