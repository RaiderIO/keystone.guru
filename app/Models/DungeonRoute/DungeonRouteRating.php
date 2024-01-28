<?php

namespace App\Models\DungeonRoute;

use App\User;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int          $id
 * @property int          $dungeon_route_id
 * @property int          $user_id
 * @property int          $rating
 *
 * @property DungeonRoute $dungeonRoute
 * @property User         $user
 *
 * @mixin Eloquent
 */
class DungeonRouteRating extends Model
{
    public $fillable   = ['dungeon_route_id', 'user_id'];
    public $timestamps = false;

    /**
     * @return BelongsTo
     */
    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}