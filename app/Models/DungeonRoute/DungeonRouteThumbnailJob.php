<?php

namespace App\Models\DungeonRoute;

use App\Models\File;
use App\Models\Floor\Floor;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int          $id
 * @property int          $dungeon_route_id
 * @property int          $floor_id
 * @property int|null     $file_id
 * @property string       $status
 * @property int|null     $viewport_width
 * @property int|null     $viewport_height
 * @property int|null     $image_width
 * @property int|null     $image_height
 * @property float|null   $zoom_level
 * @property int|null     $quality
 *
 * @property DungeonRoute $dungeonRoute
 * @property Floor        $floor
 * @property File|null    $file The generated thumbnail file, if available
 *
 * @property Carbon       $created_at
 * @property Carbon       $updated_at
 *
 * @mixin Eloquent
 */
class DungeonRouteThumbnailJob extends Model
{
    public const STATUS_QUEUED    = 'queued';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED   = 'expired';
    public const STATUS_ERROR     = 'error';

    protected $fillable = [
        'dungeon_route_id',
        'floor_id',
        'file_id',
        'status',
        'viewport_width',
        'viewport_height',
        'image_width',
        'image_height',
        'zoom_level',
        'quality',
        'created_at',
        'updated_at',
    ];

    protected $with = [
        'dungeonRoute',
        'floor',
    ];

    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function expire(): bool
    {
        // Always try to delete the image, but always return OK if it wasn't successful (there may not be an image then).
        if ($this->file instanceof File) {
            $this->file->delete();
        }

        return $this->update(['status' => self::STATUS_EXPIRED]);
    }
}
