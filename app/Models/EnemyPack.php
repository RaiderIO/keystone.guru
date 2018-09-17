<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $floor_id
 * @property string $faction
 * @property string $label
 * @property \App\Models\Floor $floor
 * @property \Illuminate\Support\Collection $enemies
 * @property \Illuminate\Support\Collection $vertices
 */
class EnemyPack extends Model
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function enemies()
    {
        return $this->hasMany('App\Models\Enemy');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function vertices()
    {
        return $this->hasMany('App\Models\EnemyPackVertex');
    }


    /**
     * Deletes all vertices that are related to this EnemyPack.
     */
    function deleteVertices()
    {
        // Load the existing vertices from the pack
        $existingVerticesIds = $this->vertices->pluck('id')->all();
        // Only if there's vertices to destroy
        if (count($existingVerticesIds) > 0) {
            // Kill them off
            EnemyPackVertex::destroy($existingVerticesIds);
        }
    }
}
