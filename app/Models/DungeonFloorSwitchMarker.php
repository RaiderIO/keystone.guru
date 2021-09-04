<?php

namespace App\Models;

use Eloquent;
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
 * @property Floor $targetfloor
 *
 * @mixin Eloquent
 */
class DungeonFloorSwitchMarker extends CacheModel
{
    protected $appends = ['direction'];
    protected $hidden = ['floor', 'targetfloor', 'laravel_through_key'];

    public $timestamps = false;

    public function getDirectionAttribute()
    {
        /** @var FloorCoupling $floorCoupling */
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
