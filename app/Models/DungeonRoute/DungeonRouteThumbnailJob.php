<?php

namespace App\Models\DungeonRoute;

use App\Models\Floor\Floor;
use App\Service\DungeonRoute\ThumbnailService;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int          $id
 * @property int          $dungeon_route_id
 * @property int          $floor_id
 * @property string       $status
 * @property int          $width
 * @property int          $height
 * @property float        $zoom_level
 * @property int          $quality
 *
 * @property DungeonRoute $dungeonRoute
 * @property Floor        $floor
 *
 * @property Carbon       $created_at
 * @property Carbon       $updated_at
 *
 * @mixin Eloquent
 */
class DungeonRouteThumbnailJob extends Model
{
    const STATUS_QUEUED    = 'queued';
    const STATUS_COMPLETED = 'completed';
    const STATUS_EXPIRED   = 'expired';
    const STATUS_ERROR     = 'error';

    protected $fillable = [
        'dungeon_route_id',
        'floor_id',
        'status',
        'width',
        'height',
        'zoom_level',
        'quality',
        'created_at',
        'updated_at',
    ];

    protected $with = [
        'dungeonRoute',
        'floor',
    ];

    /**
     * @return BelongsTo
     */
    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    /**
     * @return BelongsTo
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /**
     * @return bool
     */
    public function expire(): bool
    {
        // Always try to delete the image, but always return OK if it wasn't successful (there may not be an image then).
        $result = @unlink(
                ThumbnailService::getTargetFilePath(
                    $this->dungeonRoute,
                    $this->floor->index,
                    ThumbnailService::THUMBNAIL_CUSTOM_FOLDER_PATH,
                    'jpg'
                )
            ) || $this->status !== self::STATUS_COMPLETED;

        $this->update(['status' => self::STATUS_EXPIRED]);

        return $result;
    }
}