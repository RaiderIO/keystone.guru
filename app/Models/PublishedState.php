<?php

namespace App\Models;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * @property int                                   $id
 * @property string                                $name
 * @property EloquentCollection<int, DungeonRoute> $dungeonRoutes
 *
 * @mixin Eloquent
 */
class PublishedState extends Model
{
    use SeederModel;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
    ];

    protected $hidden = ['pivot'];

    public const UNPUBLISHED = 'unpublished';

    public const TEAM = 'team';

    public const WORLD_WITH_LINK = 'world_with_link';

    public const WORLD = 'world';

    public const ALL = [
        self::UNPUBLISHED     => 1,
        self::TEAM            => 2,
        self::WORLD_WITH_LINK => 3,
        self::WORLD           => 4,
    ];

    /**
     * Every published state, least visible first.
     */
    public const array VISIBILITY_ORDER = [
        self::UNPUBLISHED,
        self::TEAM,
        self::WORLD_WITH_LINK,
        self::WORLD,
    ];

    /** @return HasMany<DungeonRoute, $this> */
    public function dungeonRoutes(): HasMany
    {
        return $this->hasMany(DungeonRoute::class);
    }

    /**
     * @return Collection<int, string>
     */
    public static function getAvailablePublishedStates(DungeonRoute $dungeonRoute, ?User $user = null): Collection
    {
        $result = new Collection();
        $result->push(PublishedState::UNPUBLISHED);

        // An upgrade draft can never be published on its own - publishing only happens through
        // DungeonRouteUpgradeDraftService::apply(), onto the original
        if ($dungeonRoute->is_upgrade_draft) {
            return $result;
        }

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

    public static function isMoreVisibleThan(string $publishedState, string $otherPublishedState): bool
    {
        return self::getVisibilityRank($publishedState) > self::getVisibilityRank($otherPublishedState);
    }

    /**
     * @return Collection<int, string> Least visible first.
     */
    public static function getLessVisibleThan(string $publishedState): Collection
    {
        return collect(array_slice(self::VISIBILITY_ORDER, 0, self::getVisibilityRank($publishedState)));
    }

    private static function getVisibilityRank(string $publishedState): int
    {
        $rank = array_search($publishedState, self::VISIBILITY_ORDER, true);
        if ($rank === false) {
            throw new InvalidArgumentException(sprintf('Unknown published state %s', $publishedState));
        }

        return $rank;
    }
}
