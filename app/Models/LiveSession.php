<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $dungeon_route_id
 * @property int $user_id
 * @property string $public_key
 *
 * @mixin Eloquent
 */
class LiveSession extends Model
{

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Get the dungeon route that this killzone is attached to.
     *
     * @return BelongsTo
     */
    function dungeonroute(): BelongsTo
    {
        return $this->belongsTo('App\Models\DungeonRoute');
    }
}
