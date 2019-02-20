<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $floor_id
 * @property int $enemy_id
 * @property int $polyline_id
 * @property string $faction
 * @property \App\Models\Floor $floor
 * @property \App\Models\Enemy $enemy
 * @property \App\Models\Polyline $polyline
 *
 * @mixin \Eloquent
 */
class EnemyPatrol extends Model
{
    public $visible = ['id', 'floor_id', 'faction', 'polyline'];
    public $with = ['polyline'];
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function floor()
    {
        return $this->belongsTo('App\Models\Floor');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function enemy()
    {
        return $this->belongsTo('App\Models\Enemy');
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

        // Delete patrol properly if it gets deleted
        static::deleting(function ($item) {
            /** @var $item EnemyPatrol */
            $item->polyline->delete();
        });
    }
}
