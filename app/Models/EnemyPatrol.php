<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $floor_id
 * @property int $enemy_id
 * @property \App\Models\Floor $floor
 * @property \App\Models\Enemy $enemy
 * @property \Illuminate\Support\Collection $vertices
 */
class EnemyPatrol extends Model
{
    public $with = ['vertices'];
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function vertices()
    {
        return $this->hasMany('App\Models\EnemyPatrolVertex');
    }

    function deleteVertices()
    {
        // Load the existing vertices from the pack
        $existingVerticesIds = $this->vertices->pluck('id')->all();
        // Only if there's vertices to destroy
        if (count($existingVerticesIds) > 0) {
            // Kill them off
            EnemyPatrolVertex::destroy($existingVerticesIds);
        }
    }
}
