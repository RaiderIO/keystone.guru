<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $dungeon_route_id
 * @property int $user_id
 * @property \App\Models\DungeonRoute $dungeonroute
 * @property \App\User $user
 *
 * @mixin \Eloquent
 */
class DungeonRouteFavorite extends Model
{
    public $fillable = ['dungeon_route_id', 'user_id'];
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
    function user()
    {
        return $this->belongsTo('App\Models\User');
    }
}
