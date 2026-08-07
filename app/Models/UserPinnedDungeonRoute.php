<?php

namespace App\Models;

use App\Models\DungeonRoute\DungeonRoute;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A route a user has pinned to the top of their public creator profile.
 *
 * Pinning only records intent - it does not publish anything. The public profile still filters
 * pinned routes through the viewer's visibility rules, so pinning an unpublished route does not
 * expose it.
 *
 * @property int $id
 * @property int $user_id
 * @property int $dungeon_route_id
 * @property int $order
 *
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @property User              $user
 * @property DungeonRoute|null $dungeonRoute This project uses no foreign keys, so nothing at the
 *                                           database level stops a dangling pin - the pin is instead
 *                                           deleted alongside its route by
 *                                           {@see DungeonRoute::boot()}'s `deleting` listener, the
 *                                           same place every other route-owned relation is cleaned up.
 *
 * @mixin Eloquent
 */
class UserPinnedDungeonRoute extends Model
{
    /**
     * How many routes a single user may pin. Keeps the podium a curated highlight rather than a
     * second, unsorted route list.
     */
    public const int MAX_PINNED_ROUTES = 6;

    protected $fillable = [
        'user_id',
        'dungeon_route_id',
        'order',
        'updated_at',
        'created_at',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<DungeonRoute, $this> */
    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class, 'dungeon_route_id');
    }
}
