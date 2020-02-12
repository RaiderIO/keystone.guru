<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int
 * @property $floor_id int
 * @property $target_floor_id int
 * @property $lat float
 * @property $lng float
 * @property $direction string
 *
 * @mixin \Eloquent
 */
class DungeonFloorSwitchMarker extends Model
{
    protected $appends = ['direction'];

    public $timestamps = false;

    public function getDirectionAttribute(){
        $floorCoupling = FloorCoupling::where('floor1_id', $this->floor_id)->where('floor2_id', $this->target_floor_id)->first();

        return $floorCoupling === null ? 'unknown' : $floorCoupling->direction;
    }

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
