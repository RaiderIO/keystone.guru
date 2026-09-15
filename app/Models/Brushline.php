<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 15-2-2019
 * Time: 12:34
 */

namespace App\Models;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\Interfaces\HasPolylineInterface;
use App\Models\Traits\HasPolyline;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int $dungeon_route_id
 * @property int $floor_id
 * @property int $polyline_id
 *
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @property DungeonRoute  $dungeonRoute
 * @property Polyline|null $polyline
 * @property Floor         $floor
 *
 * @mixin Eloquent
 */
class Brushline extends Model implements HasPolylineInterface
{
    use HasPolyline;

    protected $visible = [
        'id',
        'floor_id',
        'polyline',
    ];

    protected $fillable = [
        'dungeon_route_id',
        'floor_id',
        'polyline_id',
        'created_at',
        'updated_at',
    ];

    protected $with = ['polyline'];

    protected function casts(): array
    {
        return [
            'id'               => 'int',
            'dungeon_route_id' => 'int',
            'floor_id'         => 'int',
            'polyline_id'      => 'int',
        ];
    }

    /**
     * Get the dungeon route that this brushline is attached to.
     *
     * @return BelongsTo<DungeonRoute, $this>
     */
    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    /**
     * Get the floor that this polyline is drawn on.
     *
     * @return BelongsTo<Floor, $this>
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        // Delete Brushline properly if it gets deleted
        static::deleting(static function (Brushline $brushline) {
            $brushline->polyline()->delete();
        });
    }
}
