<?php

namespace App\Models\DungeonRoute;

use App\Models\GameVersion\GameVersion;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\Season;
use App\Models\Team;
use App\Models\Traits\GeneratesPublicKey;
use App\Models\User;
use App\Models\UserPinnedDungeonRouteCollection;
use Database\Factories\DungeonRoute\DungeonRouteCollectionFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Override;

/**
 * A named, shareable list of dungeon routes put together by a single user, for example "my routes
 * for this week". Shares its visibility mechanics with DungeonRoute - a published state and a
 * public key - so that a collection can be linked around exactly like a route can.
 *
 * A collection is not a tag with a URL: it is ordered, capped, and made to be read by other people, where a Tag is
 * an unbounded private label that only ever filters lists. A collection carries no tags of its own - MAX_COLLECTIONS
 * keeps the list short enough that its game version, season, category and visibility organize it already.
 *
 * @property int         $id
 * @property int         $user_id
 * @property int|null    $team_id
 * @property int|null    $dungeon_route_collection_category_id
 * @property int         $game_version_id
 * @property int|null    $season_id                            Set for a season set, null for a free-form collection.
 * @property string      $public_key
 * @property int         $published_state_id
 * @property string      $name
 * @property string|null $description
 *
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @property User                                                 $user
 * @property Team|null                                            $team
 * @property DungeonRouteCollectionCategory|null                  $dungeonRouteCollectionCategory
 * @property GameVersion                                          $gameVersion
 * @property Season|null                                          $season
 * @property PublishedState                                       $publishedState
 * @property EloquentCollection<int, DungeonRouteCollectionRoute> $dungeonRouteCollectionRoutes
 * @property EloquentCollection<int, DungeonRoute>                $dungeonRoutes
 *
 * @mixin Eloquent
 */
class DungeonRouteCollection extends Model
{
    use GeneratesPublicKey;
    /** @use HasFactory<DungeonRouteCollectionFactory> */
    use HasFactory;

    /**
     * How many routes a single collection may hold. A collection is meant to be a curated list -
     * without a cap the public collection page would eagerly load an unbounded amount of routes.
     * Two routes for every dungeon of a season is plenty for a curated set.
     */
    public const int MAX_ROUTES = 16;

    /**
     * How many routes of one dungeon a single collection may hold. Only routes joining are checked against it,
     * so routes a collection already holds may stay.
     */
    public const int MAX_ROUTES_PER_DUNGEON = 2;

    /**
     * How many collections a single user may own.
     */
    public const int MAX_COLLECTIONS = 25;

    /**
     * The published states a collection may be in. Unlike a dungeon route a collection is never
     * a draft that must be completed first, so all states are always available.
     */
    public const array AVAILABLE_PUBLISHED_STATES = [
        PublishedState::UNPUBLISHED,
        PublishedState::TEAM,
        PublishedState::WORLD_WITH_LINK,
        PublishedState::WORLD,
    ];

    protected $fillable = [
        'user_id',
        'team_id',
        'dungeon_route_collection_category_id',
        'game_version_id',
        'season_id',
        'public_key',
        'published_state_id',
        'name',
        'description',
        'updated_at',
        'created_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'game_version_id' => 'integer',
            'season_id'       => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return BelongsTo<DungeonRouteCollectionCategory, $this> */
    public function dungeonRouteCollectionCategory(): BelongsTo
    {
        return $this->belongsTo(DungeonRouteCollectionCategory::class);
    }

    /** @return BelongsTo<GameVersion, $this> */
    public function gameVersion(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class);
    }

    /** @return BelongsTo<Season, $this> */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /** @return BelongsTo<PublishedState, $this> */
    public function publishedState(): BelongsTo
    {
        return $this->belongsTo(PublishedState::class);
    }

    /** @return HasMany<DungeonRouteCollectionRoute, $this> */
    public function dungeonRouteCollectionRoutes(): HasMany
    {
        return $this->hasMany(DungeonRouteCollectionRoute::class)->orderBy('order');
    }

    /** @return BelongsToMany<DungeonRoute, $this> */
    public function dungeonRoutes(): BelongsToMany
    {
        return $this->belongsToMany(DungeonRoute::class, 'dungeon_route_collection_routes')
            ->withPivot('order')
            ->orderBy('dungeon_route_collection_routes.order');
    }

    /** @return HasMany<UserPinnedDungeonRouteCollection, $this> */
    public function userPinnedDungeonRouteCollections(): HasMany
    {
        return $this->hasMany(UserPinnedDungeonRouteCollection::class);
    }

    /**
     * A season set holds the routes of one season, one slot per dungeon of its pool; any other collection is
     * free-form.
     */
    public function isSeasonSet(): bool
    {
        return $this->season_id !== null;
    }

    /**
     * Whether a route may be in this collection: its own mapping version must be of the collection's game version
     * (never judged by its dungeon, which spans several game versions), and for a season set the route must be of
     * the collection's season. Expects the route's mapping version to be loaded.
     */
    public function mayContainDungeonRoute(DungeonRoute $dungeonRoute): bool
    {
        $mappingVersion = $dungeonRoute->mappingVersion;

        if ($mappingVersion === null) {
            return false;
        }

        if ($mappingVersion->game_version_id !== $this->game_version_id) {
            return false;
        }

        return !$this->isSeasonSet() || $dungeonRoute->season_id === $this->season_id;
    }

    public function getPublishedStateName(): string
    {
        return array_search($this->published_state_id, PublishedState::ALL, true) ?: PublishedState::UNPUBLISHED;
    }

    public function isOwnedByUser(?User $user = null): bool
    {
        // Can't have a function as a default value
        if ($user === null) {
            $user = Auth::user();
        }

        return $user !== null && $this->user_id === $user->id;
    }

    public function mayUserView(?User $user): bool
    {
        return match ($this->published_state_id) {
            PublishedState::ALL[PublishedState::UNPUBLISHED]                                                 => $this->mayUserEdit($user),
            PublishedState::ALL[PublishedState::TEAM]                                                        => ($this->team !== null && $this->team->isUserMember($user)) || ($user !== null && $user->hasRole(Role::ROLE_ADMIN)),
            PublishedState::ALL[PublishedState::WORLD_WITH_LINK], PublishedState::ALL[PublishedState::WORLD] => true,
            default                                                                                          => false,
        };
    }

    public function mayUserEdit(?User $user): bool
    {
        return $user !== null && ($this->isOwnedByUser($user) || $user->hasRole(Role::ROLE_ADMIN));
    }

    /**
     * The routes of this collection that the passed user is actually allowed to see. A collection
     * being public never publishes the routes inside it - an unpublished route stays hidden.
     *
     * @return Collection<int, DungeonRoute>
     */
    public function getViewableDungeonRoutes(?User $user): Collection
    {
        return $this->dungeonRoutes
            ->filter(static fn(DungeonRoute $dungeonRoute): bool => $dungeonRoute->mayUserView($user))
            ->values();
    }

    /**
     * Eager load everything the collection tile and mayUserView() read, for the collection and its routes.
     *
     * @param  Builder<self> $query
     * @return Builder<self>
     */
    #[Scope]
    protected function withTileRelations(Builder $query): Builder
    {
        return $query->with([
            'dungeonRouteCollectionCategory',
            'team',
            'gameVersion',
            'season.dungeons',
            'dungeonRoutes.thumbnails',
            'dungeonRoutes.dungeon',
            'dungeonRoutes.team',
        ]);
    }

    #[Override]
    public function getRouteKeyName(): string
    {
        return 'public_key';
    }

    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        // This project does not use foreign keys, so the couplings must be cleaned up by hand
        static::deleting(static function (DungeonRouteCollection $dungeonRouteCollection) {
            $dungeonRouteCollection->dungeonRouteCollectionRoutes()->delete();
            $dungeonRouteCollection->userPinnedDungeonRouteCollections()->delete();
        });
    }
}
