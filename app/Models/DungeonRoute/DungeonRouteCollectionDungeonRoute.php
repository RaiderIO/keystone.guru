<?php

namespace App\Models\DungeonRoute;

use App\Models\DungeonRoute;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id The ID.
 * @property int $dungeon_route_collection_id The ID of the collection.
 * @property int $dungeon_route_id The ID of the dungeon route
 *
 * @property DungeonRouteCollection $dungeonRouteCollection
 * @property DungeonRoute $dungeonRoute
 *
 * @mixin Eloquent
 */
class DungeonRouteCollectionDungeonRoute extends Model
{
    protected $fillable = [
        'dungeon_route_collection_id',
        'dungeon_route_id',
    ];

    /**
     * @return BelongsTo
     */
    public function dungeonRouteCollection(): BelongsTo
    {
        return $this->belongsTo(DungeonRouteCollection::class);
    }

    /**
     * @return BelongsTo
     */
    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }
}
