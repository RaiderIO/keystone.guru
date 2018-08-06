<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int
 * @property $floor_id int
 * @property $target_floor_id int
 * @property $lat float
 * @property $lng float
 */
class DungeonFloorSwitchMarker extends Model
{

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function floor()
    {
        return $this->belongsTo('App\Models\Floor');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function targetfloor()
    {
        return $this->belongsTo('App\Models\Floor', 'target_floor_id');
    }
}
