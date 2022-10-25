<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $mapping_version_id
 * @property int $floor_id
 * @property int $target_floor_id
 * @property float $lat
 * @property float $lng
 * @property string $direction
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

    /**
     * @return string
     */
    public function getDirectionAttribute(): string
    {
        /** @var FloorCoupling $floorCoupling */
        $floorCoupling = FloorCoupling::where('floor1_id', $this->floor_id)->where('floor2_id', $this->target_floor_id)->first();

        return $floorCoupling === null ? 'unknown' : $floorCoupling->direction;
    }

    /**
     * @return BelongsTo
     */
    function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /**
     * @return BelongsTo
     */
    function targetfloor(): BelongsTo
    {
        return $this->belongsTo(Floor::class, 'target_floor_id');
    }
}
