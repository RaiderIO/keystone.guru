<?php

namespace App\Models\DungeonRoute;

use App\Models\Floor\Floor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int          $id
 * @property int          $dungeon_route_id
 * @property int          $floor_id
 * @property string       $status
 * @property int          $width
 * @property int          $height
 * @property int          $quality
 *
 * @property DungeonRoute $dungeonRoute
 * @property Floor        $floor
 *
 * @property Carbon       $created_at
 * @property Carbon       $updated_at
 */
class DungeonRouteThumbnailJob extends Model
{
    protected $fillable = [
        'dungeon_route_id',
        'floor_id',
        'status',
        'width',
        'height',
        'quality',
        'created_at',
        'updated_at',
    ];

    /**
     * @return BelongsTo
     */
    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }
}
