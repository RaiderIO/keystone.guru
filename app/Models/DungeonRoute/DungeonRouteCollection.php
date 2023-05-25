<?php

namespace App\Models\DungeonRoute;

use App\Models\DungeonRoute;
use App\User;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;

/**
 * @property int $id The ID.
 * @property int $user_id The user that made this route collection.
 * @property string $name The name of the collection.
 * @property string $description The description of the collection.
 *
 * @property User $user
 * @property Collection|DungeonRoute[] $dungeonRoutes
 * @property Collection|DungeonRouteCollectionDungeonRoute[] $dungeonRouteCollectionDungeonRoutes
 *
 * @mixin Eloquent
 */
class DungeonRouteCollection extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasManyThrough
     */
    public function dungeonRoutes(): HasManyThrough
    {
        return $this->hasManyThrough(DungeonRoute::class, DungeonRouteCollectionDungeonRoute::class);
    }

    /**
     * @return HasMany
     */
    public function dungeonRouteCollectionDungeonRoutes(): HasMany
    {
        return $this->hasMany(DungeonRouteCollectionDungeonRoute::class);
    }
}
