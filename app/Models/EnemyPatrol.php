<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $floor_id
 * @property int $enemy_id
 * @property string $faction
 * @property string $vertices_json
 * @property \App\Models\Floor $floor
 * @property \App\Models\Enemy $enemy
 * @property \Illuminate\Support\Collection $vertices
 */
class EnemyPatrol extends Model
{
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
}
