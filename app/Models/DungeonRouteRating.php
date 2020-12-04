<?php

namespace App\Models;

use App\User;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $dungeon_route_id
 * @property int $user_id
 * @property int $rating
 * @property DungeonRoute $dungeonroute
 * @property User $user
 *
 * @mixin Eloquent
 */
class DungeonRouteRating extends Model
{
    public $fillable = ['dungeon_route_id', 'user_id'];
    public $timestamps = false;

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
    function user()
    {
        return $this->belongsTo('App\Models\User');
    }
}
