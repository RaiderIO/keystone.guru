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
 * @property $id int
 * @property $dungeon_route_id int
 * @property $floor_id int
 * @property $polyline_id int
 * @property $updated_at string
 * @property $created_at string
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
    function dungeonroute()
    {
        return $this->belongsTo('App\Models\DungeonRoute');
    }

    /**
     * Get the dungeon route that this brushline is attached to.
     *
     * @return hasOne
     */
    function polyline()
    {
        return $this->hasOne('App\Models\Polyline', 'model_id')->where('model_class', get_class($this));
    }

    /**
     * Get the floor that this polyline is drawn on.
     *
     * @return BelongsTo
     */
    function floor()
    {
        return $this->belongsTo('App\Models\Floor');
    }

    public static function boot()
    {
        parent::boot();

        // Delete Brushline properly if it gets deleted
        static::deleting(function ($item) {
            /** @var $item Brushline */
            if ($item->polyline !== null) {
                $item->polyline->delete();
            }
        });
    }
}
