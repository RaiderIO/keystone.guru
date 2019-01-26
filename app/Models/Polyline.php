<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int
 * @property $dungeon_route_id int
 * @property $floor_id int
 * @property $type string
 * @property $color string
 * @property $weight int
 * @property $dungeonroute DungeonRoute
 * @property $vertices_json string JSON encoded vertices
 */
class Polyline extends Model
{
    /**
     * Get the dungeon route that this polyline is attached to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function dungeonroute()
    {
        return $this->belongsTo('App\Models\DungeonRoute');
    }
}
