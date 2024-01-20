<?php

namespace App\Models\DungeonRoute;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int    $id
 * @property int    $dungeon_route_id
 * @property int    $floor_id
 * @property string $status
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class DungeonRouteThumbnailJob extends Model
{
    protected $fillable = [
        'dungeon_route_id',
        'floor_id',
        'status',
    ];

    /**
     * @return BelongsTo
     */
    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }
}
