<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\hasOne;

/**
 * @property int $id
 * @property int $mapping_version_id
 * @property int $floor_id
 * @property int $polyline_id
 * @property string $teeming
 * @property string $faction
 *
 * @property Floor $floor
 * @property Polyline $polyline
 *
 * @mixin Eloquent
 */
class EnemyPatrol extends CacheModel
{
    public $visible = ['id', 'mapping_version_id', 'floor_id', 'teeming', 'faction', 'polyline'];
    public $with = ['polyline'];
    public $timestamps = false;

    /**
     * @return BelongsTo
     */
    function floor()
    {
        return $this->belongsTo('App\Models\Floor');
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

    public static function boot()
    {
        parent::boot();

        // Delete patrol properly if it gets deleted
        static::deleting(function ($item) {
            /** @var $item EnemyPatrol */
            if ($item->polyline !== null) {
                $item->polyline->delete();
            }
        });
    }
}
