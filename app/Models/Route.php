<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int
 * @property $dungeonroute DungeonRoute
 */
class Route extends Model
{

    /**
     * Get the dungeon route that this route is attached to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    function dungeonroute()
    {
        return $this->hasOne('App\Models\DungeonRoute');
    }
}
