<?php

namespace App\Models\Floor;

use App\Models\CacheModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int   $id
 * @property int   $floor_id
 * @property int   $target_floor_id
 * @property float $lat
 * @property float $lng
 * @property float $size
 * @property float $rotation
 *
 * @property Floor $floor
 * @property Floor $targetFloor
 *
 * @property Collection|FloorUnionArea[] $floorUnionAreas
 *
 * @mixin Eloquent
 */
class FloorUnion extends CacheModel
{
    protected $fillable = [
        'floor_id',
        'target_floor_id',
        'lat',
        'lng',
        'size',
        'rotation',
    ];

    protected $with = [
        'floorUnionAreas'
    ];

    /**
     * @return BelongsTo
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /**
     * @return BelongsTo
     */
    public function targetFloor(): BelongsTo
    {
        return $this->belongsTo(Floor::class, 'target_floor_id');
    }

    /**
     * @return HasMany
     */
    public function floorUnionAreas(): HasMany
    {
        return $this->hasMany(FloorUnionArea::class);
    }
}
