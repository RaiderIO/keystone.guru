<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 15-2-2019
 * Time: 12:34
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
 */
class Brushline extends Model
{
    public $visible = ['id', 'polyline'];
    public $with = ['polyline'];

    /**
     * Get the dungeon route that this brushline is attached to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function dungeonroute()
    {
        return $this->belongsTo('App\Models\DungeonRoute');
    }

    /**
     * Get the floor that this polyline is drawn on.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function floor()
    {
        return $this->belongsTo('App\Models\Floor');
    }

    /**
     * Get the dungeon route that this brushline is attached to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    function polyline()
    {
        return $this->hasOne('App\Models\Polyline', 'model_id')->where('model_class', get_class($this));
    }

    public static function boot()
    {
        parent::boot();

        // Delete Path properly if it gets deleted
        static::deleting(function ($item) {
            /** @var $item Brushline */
            if ($item->polyline !== null) {
                $item->polyline->delete();
            }
        });
    }
}
