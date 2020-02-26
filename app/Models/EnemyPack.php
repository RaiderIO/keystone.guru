<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $floor_id
 * @property string $teeming
 * @property string $faction
 * @property string $label
 * @property string $vertices_json
 *
 * @property \App\Models\Floor $floor
 * @property \Illuminate\Support\Collection $enemies
 *
 * @mixin \Eloquent
 */
class EnemyPack extends Model
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function enemies()
    {
        return $this->hasMany('App\Models\Enemy');
    }
}
