<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $dungeon_route_id
 * @property int $raid_marker_id
 * @property int $enemy_id
 */
class DungeonRouteEnemyRaidMarker extends Model
{
    public $hidden = ['dungeon_route_id'];
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function dungeonroute()
    {
        return $this->belongsTo('App\Models\DungeonRoute');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function raidmarker()
    {
        return $this->belongsTo('App\Models\RaidMarker');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function enemy()
    {
        return $this->belongsTo('App\Models\Enemy');
    }
}
