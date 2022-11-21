<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 15-2-2019
 * Time: 12:34
 */

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\hasOne;

/**
 * @property int $id
 * @property int $dungeon_route_id
 * @property int $floor_id
 * @property int $polyline_id
 * @property string $updated_at
 * @property string $created_at
 *
 * @property DungeonRoute $dungeonroute
 * @property Polyline $polyline
 * @property Floor $floor
 *
 * @mixin Eloquent
 */
class Brushline extends Model
{
    public $visible = ['id', 'floor_id', 'polyline'];
    public $with = ['polyline'];

    /**
     * Get the dungeon route that this brushline is attached to.
     *
     * @return BelongsTo
     */
    public function dungeonroute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    /**
     * Get the dungeon route that this brushline is attached to.
     *
     * @return HasOne
     */
    public function polyline(): HasOne
    {
        return $this->hasOne(Polyline::class, 'model_id')->where('model_class', get_class($this));
    }

    /**
     * Get the floor that this polyline is drawn on.
     *
     * @return BelongsTo
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public static function boot()
    {
        parent::boot();

        // Delete Brushline properly if it gets deleted
        static::deleting(function ($item) {
            /** @var $item Brushline */
            $item->polyline()->delete();
        });
    }
}
