<?php

namespace App\Models;

use App\Models\DungeonRoute\DungeonRouteCollection;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A collection a user has pinned to the top of their public creator profile.
 *
 * Deliberately a table of its own rather than a polymorphic rework of user_pinned_dungeon_routes:
 * the routes table is already shipped, and an additive table cannot break a rolling deploy the way
 * a rename would.
 *
 * Pinning only records intent - it does not publish anything. The public profile still filters
 * pinned collections through the viewer's visibility rules.
 *
 * @property int $id
 * @property int $user_id
 * @property int $dungeon_route_collection_id
 * @property int $order
 *
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @property User                        $user
 * @property DungeonRouteCollection|null $dungeonRouteCollection This project uses no foreign keys,
 *                                                               so a collection that was since
 *                                                               deleted leaves a dangling pin
 *
 * @mixin Eloquent
 */
class UserPinnedDungeonRouteCollection extends Model
{
    /**
     * How many collections a single user may pin. Kept in step with the route pins so the podium
     * stays a curated highlight rather than a second listing.
     */
    public const int MAX_PINNED_COLLECTIONS = 6;

    protected $fillable = [
        'user_id',
        'dungeon_route_collection_id',
        'order',
        'updated_at',
        'created_at',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<DungeonRouteCollection, $this> */
    public function dungeonRouteCollection(): BelongsTo
    {
        return $this->belongsTo(DungeonRouteCollection::class, 'dungeon_route_collection_id');
    }
}
