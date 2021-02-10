<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $floor_id
 * @property string $teeming
 * @property string $faction
 * @property string $label
 * @property string $vertices_json
 *
 * @property Floor $floor
 * @property Collection $enemies
 *
 * @mixin Eloquent
 */
class EnemyPack extends CacheModel
{
    public $timestamps = false;

    /**
     * @return BelongsTo
     */
    function floor()
    {
        return $this->belongsTo('App\Models\Floor');
    }

    /**
     * @return HasMany
     */
    function enemies()
    {
        return $this->hasMany('App\Models\Enemy');
    }
}
