<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int
 * @property $floor_id int
 * @property $lat float
 * @property $lng float
 */
class DungeonStartMarker extends Model
{
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function floor()
    {
        return $this->belongsTo('App\Models\Floor');
    }
}
