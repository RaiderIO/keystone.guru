<?php

namespace App\Models;

use App\Models\Traits\Reportable;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $dungeon_route_id
 * @property int $enemy_id
 * @property int $floor_id
 * @property double $lat
 * @property double $lng
 *
 * @property DungeonRoute $dungeonroute
 * @property Enemy $enemy
 * @property Floor $floor
 *
 * @mixin Eloquent
 */
class PridefulEnemy extends Model
{
    use Reportable;

    /**
     * @return BelongsTo
     */
    function dungeonroute()
    {
        return $this->belongsTo('App\Models\DungeonRoute');
    }

    /**
     * @return BelongsTo
     */
    function enemy()
    {
        return $this->belongsTo('App\Models\Enemy');
    }

    /**
     * @return BelongsTo
     */
    function floor()
    {
        return $this->belongsTo('App\Models\Floor');
    }
}
