<?php

namespace App\Models;

use App\Models\Traits\HasLinkedAwakenedObelisk;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
class Path extends Model
{
    use HasLinkedAwakenedObelisk;

    public $visible = ['id', 'floor_id', 'linked_awakened_obelisk_id', 'polyline'];
    public $fillable = ['dungeon_route_id', 'floor_id', 'polyline_id'];
    public $with = ['polyline', 'linkedawakenedobelisks'];
    protected $appends = ['linked_awakened_obelisk_id'];

    /**
     * Get the dungeon route that this route is attached to.
     *
     * @return BelongsTo
     */
    function dungeonroute()
    {
        return $this->belongsTo('App\Models\DungeonRoute');
    }

    /**
     * @return HasOne
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

        // Delete Path properly if it gets deleted
        static::deleting(function ($item)
        {
            /** @var $item HasLinkedAwakenedObelisk */
            if ($item->linkedawakenedobelisks !== null) {
                $item->linkedawakenedobelisks()->delete();
            }

            /** @var $item Path */
            if ($item->polyline !== null) {
                $item->polyline->delete();
            }
        });
    }
}
