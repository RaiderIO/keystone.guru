<?php

namespace App\Models\DungeonRoute;

use App\Models\File;
use App\Models\Floor\Floor;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int                          $id
 * @property int                          $dungeon_route_id
 * @property int                          $floor_id
 * @property int                          $file_id
 * @property bool                         $custom           Deprecated: superseded by the 'custom' variant. Kept and dual-written until the follow-up migration drops it.
 * @property DungeonRouteThumbnailVariant $variant          Which render variant this thumbnail is
 *
 * @property DungeonRoute $dungeonRoute
 * @property Floor        $floor
 * @property File|null    $file
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
        'custom',
        'variant',
        'created_at',
        'updated_at',
    ];

    protected $with = [
        'floor',
        'file',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'variant' => DungeonRouteThumbnailVariant::class,
        ];
    }

    /** @return BelongsTo<DungeonRoute, $this> */
    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    /** @return BelongsTo<Floor, $this> */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /** @return BelongsTo<File, $this> */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    #[Override]
    public static function booted(): void
    {
        parent::booted();

        static::deleting(function (DungeonRouteThumbnail $thumbnail) {
            $thumbnail->file?->delete();
        });
    }
}
