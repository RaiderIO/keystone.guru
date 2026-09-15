<?php

namespace App\Models\DungeonRoute;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The coupling between a collection and one of the routes in it, keeping the order the author
 * arranged them in.
 *
 * @property int $id
 * @property int $dungeon_route_collection_id
 * @property int $dungeon_route_id
 * @property int $order
 *
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @property DungeonRouteCollection $dungeonRouteCollection
 * @property DungeonRoute|null      $dungeonRoute           This project uses no foreign keys, so a route that
 *                                                          was since deleted leaves a dangling coupling behind
 *
 * @mixin Eloquent
 */
class DungeonRouteCollectionRoute extends Model
{
    protected $fillable = [
        'dungeon_route_collection_id',
        'dungeon_route_id',
        'order',
        'updated_at',
        'created_at',
    ];

    /** @return BelongsTo<DungeonRouteCollection, $this> */
    public function dungeonRouteCollection(): BelongsTo
    {
        return $this->belongsTo(DungeonRouteCollection::class);
    }

    /** @return BelongsTo<DungeonRoute, $this> */
    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }
}
