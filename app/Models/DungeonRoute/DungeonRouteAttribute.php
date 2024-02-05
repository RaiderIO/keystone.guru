<?php

namespace App\Models\DungeonRoute;

use App\Models\RouteAttribute;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int            $id
 * @property int            $dungeon_route_id
 * @property int            $route_attribute_id
 *
 * @property DungeonRoute   $dungeonRoute
 * @property RouteAttribute $routeAttribute
 *
 * @mixin Eloquent
 */
class DungeonRouteAttribute extends Model
{
    public    $timestamps = false;
    protected $fillable   = [
        'route_attribute_id',
        'dungeon_route_id',
    ];

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
    public function routeAttribute(): BelongsTo
    {
        return $this->belongsTo(RouteAttribute::class);
    }
}
