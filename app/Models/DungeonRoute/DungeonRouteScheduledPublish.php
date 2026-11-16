<?php

namespace App\Models\DungeonRoute;

use App\Models\PublishedState;
use App\Models\User;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int      $id
 * @property int      $dungeon_route_id
 * @property int|null $user_id          The user that scheduled the publish
 * @property string   $published_state  One of PublishedState::TEAM, WORLD_WITH_LINK, WORLD
 * @property Carbon   $publish_at
 * @property Carbon   $updated_at
 * @property Carbon   $created_at
 *
 * @property DungeonRoute $dungeonRoute
 * @property User|null    $user
 *
 * @mixin Eloquent
 */
class DungeonRouteScheduledPublish extends Model
{
    /**
     * Returns all valid publication states that can be scheduled.
     */
    public const array SCHEDULABLE_PUBLISH_STATES = [
        PublishedState::WORLD_WITH_LINK,
        PublishedState::WORLD,
    ];

    protected $fillable = [
        'dungeon_route_id',
        'user_id',
        'published_state',
        'publish_at',
    ];

    public function casts(): array
    {
        return [
            'publish_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<DungeonRoute, $this> */
    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
