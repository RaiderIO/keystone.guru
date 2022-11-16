<?php

namespace App\Models;

use App\Models\Patreon\PatreonBenefit;
use App\User;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 *
 * @property Collection|DungeonRoute[] $dungeonroutes
 *
 * @mixin Eloquent
 */
class PublishedState extends CacheModel
{
    public const UNPUBLISHED     = 'unpublished';
    public const TEAM            = 'team';
    public const WORLD_WITH_LINK = 'world_with_link';
    public const WORLD           = 'world';

    public const ALL = [
        self::UNPUBLISHED     => 1,
        self::TEAM            => 2,
        self::WORLD_WITH_LINK => 3,
        self::WORLD           => 4,
    ];

    public $timestamps = false;

    protected $fillable = [
        'id', 'name',
    ];

    protected $hidden = ['pivot'];

    /**
     * @return HasMany
     */
    public function dungeonroutes(): HasMany
    {
        return $this->hasMany(DungeonRoute::class);
    }

    /**
     * @param DungeonRoute $dungeonRoute
     * @param User|null $user
     * @return Collection|string[]
     */
    public static function getAvailablePublishedStates(DungeonRoute $dungeonRoute, ?User $user = null): Collection
    {
        $result = new Collection();
        $result->push(PublishedState::UNPUBLISHED);
        $result->push(PublishedState::TEAM);


        if ($user !== null && $user->hasPatreonBenefit(PatreonBenefit::UNLISTED_ROUTES)) {
            $result->push(PublishedState::WORLD_WITH_LINK);
        }

        // Only active dungeons may be made public
        if ($dungeonRoute->dungeon->active) {
            $result->push(PublishedState::WORLD);
        }

        return $result;
    }

    public static function boot()
    {
        parent::boot();

        // This model may NOT be deleted, it's read only!
        static::deleting(function ($someModel) {
            return false;
        });
    }
}
