<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int
 * @property $dungeon_route_id int
 * @property $floor_id int
 * @property $color string
 * @property $dungeonroute DungeonRoute
 * @property \Illuminate\Support\Collection $vertices
 */
class Route extends Model
{
    public $with = ['vertices'];

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
    function vertices()
    {
        return $this->hasMany('App\Models\RouteVertex');
    }

    /**
     * Deletes all vertices that are related to this Route.
     */
    function deleteVertices()
    {
        // Load the existing vertices from the pack
        $existingVerticesIds = $this->vertices->pluck('id')->all();
        // Only if there's vertices to destroy
        if (count($existingVerticesIds) > 0) {
            // Kill them off
            RouteVertex::destroy($existingVerticesIds);
        }
    }
}
