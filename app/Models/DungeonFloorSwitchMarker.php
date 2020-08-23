<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property $id int
 * @property $floor_id int
 * @property $target_floor_id int
 * @property $lat float
 * @property $lng float
 * @property $direction string
 *
 * @property Floor $floor
 *
 * @mixin Eloquent
 */
class DungeonFloorSwitchMarker extends Model
{
    protected $appends = ['direction'];
    protected $hidden = ['floor', 'targetfloor'];

    public $timestamps = false;

    public function getDirectionAttribute(){
        $floorCoupling = FloorCoupling::where('floor1_id', $this->floor_id)->where('floor2_id', $this->target_floor_id)->first();

        return $floorCoupling === null ? 'unknown' : $floorCoupling->direction;
    }

    /**
     * @return BelongsTo
     */
    function floor()
    {
        return $this->belongsTo('App\Models\Floor');
    }

    /**
     * @return BelongsTo
     */
    function targetfloor()
    {
        return $this->belongsTo('App\Models\Floor', 'target_floor_id');
    }
}
