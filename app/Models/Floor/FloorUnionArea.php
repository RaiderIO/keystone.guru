<?php

namespace App\Models\Floor;

use App\Models\CacheModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int    $id
 * @property int    $floor_union_id
 * @property string $vertices_json
 *
 * @mixin Eloquent
 */
class FloorUnionArea extends CacheModel
{
    protected $fillable = [
        'floor_union_id',
        'vertices_json',
    ];

    /**
     * @return BelongsTo
     */
    public function floorUnion(): BelongsTo
    {
        return $this->belongsTo(FloorUnion::class);
    }

}
