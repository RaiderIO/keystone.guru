<?php

namespace App\Models\DungeonRoute;

use App\Models\File;
use App\Models\Floor\Floor;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int  $id
 * @property int  $dungeon_route_id
 * @property int  $floor_id
 * @property int  $file_id
 * @property bool $custom           True if this thumbnail was requested through the API with custom parameters
 *
 * @property DungeonRoute $dungeonRoute
 * @property Floor        $floor
 * @property File         $file
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @mixin Eloquent
 */
class DungeonRouteThumbnail extends Model
{
    protected $fillable = [
        'dungeon_route_id',
        'floor_id',
        'file_id',
        'created_at',
        'updated_at',
    ];

    protected $with = [
        'floor',
        'file',
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

    public static function booted(): void
    {
        parent::booted();

        static::deleting(function (DungeonRouteThumbnail $thumbnail) {
            $thumbnail->file?->delete();
        });
    }
}
