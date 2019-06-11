<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $floor_id
 * @property int $dungeon_route_id
 * @property int $game_icon_id
 * @property float $lat
 * @property float $lng
 * @property boolean $always_visible
 * @property string $comment
 *
 * @property \App\Models\DungeonRoute $dungeonroute
 * @property \App\User $user
 *
 * @mixin \Eloquent
 */
class MapComment extends Model
{
    protected $visible = ['id', 'floor_id', 'game_icon_id', 'lat', 'lng', 'always_visible', 'comment'];

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
    function dungeonroute()
    {
        return $this->belongsTo('App\Models\DungeonRoute');
    }
}
