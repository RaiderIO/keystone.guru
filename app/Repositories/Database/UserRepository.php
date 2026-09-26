<?php

namespace App\Repositories\Database;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Service\Creator\Enums\CreatorDirectorySort;
use Illuminate\Database\Eloquent\Builder;

class UserRepository extends DatabaseRepository implements UserRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(User::class);
    }

    /**
     * Deliberately a join against a pre-aggregated derived table rather than withCount() + having().
     * A correlated withCount subquery cannot be filtered by an index, so MySQL had to evaluate one
     * COUNT per non-opted-out user - a full scan of `users` - before it could discard anyone, and
     * then materialise every matching `users.*` row (including the `bio` TEXT column) into a temp
     * table to sort it. Grouping `dungeon_routes` first inverts that: the aggregate runs once
     * against the published_state_id index, and `users` is then reached by primary key.
     *
     *   before  users type=ALL    key=NULL     rows=173  Using temporary; Using filesort
     *   after   users type=eq_ref key=PRIMARY  rows=1
     *
     * The cost now scales with published routes and qualifying creators instead of with total
     * registrations, which matters for a page intended to become public and is not cached.
     *
     * The category filter is an EXISTS against dungeon_route_collections, which MySQL resolves per
     * candidate creator through the user_id index (type=ref, rows=1) - so it costs one index lookup
     * per creator that already survived the join, not a scan:
     *
     *   dungeon_route_collections  type=ref  key=..._user_id_index  rows=1
     *
     * @return Builder<User>
     */
    public function buildListedCreatorsQuery(
        ?int                 $categoryId = null,
        ?int                 $seasonId = null,
        CreatorDirectorySort $sort = CreatorDirectorySort::MostRoutes,
    ): Builder {
        $builder = $this->buildListedCreatorsBaseQuery($categoryId, $seasonId);

        if ($sort === CreatorDirectorySort::ActiveThisSeason && $seasonId !== null) {
            $builder
                ->orderByRaw('published_routes.season_route_count > 0 DESC')
                ->orderByDesc('published_routes.season_popularity');
        }

        return $builder
            ->orderByDesc('published_routes.published_route_count')
            // Stable tiebreak so pagination cannot repeat or skip a creator between pages
            ->orderBy('users.id');
    }

    /**
     * The dungeon aggregate is joined on top of the listed-creators query, so a creator must clear
     * the same site-wide bar (and not have opted out) before a dungeon page may feature them.
     */
    public function buildFeaturedCreatorsForDungeonQuery(int $dungeonId, ?int $seasonId): Builder
    {
        $dungeonRouteStats = DungeonRoute::query()
            ->selectRaw('author_id, COUNT(*) AS dungeon_route_count, SUM(popularity) AS dungeon_popularity')
            ->where('published_state_id', PublishedState::ALL[PublishedState::WORLD])
            ->where('dungeon_id', $dungeonId)
            ->when($seasonId !== null, static fn(Builder $builder): Builder => $builder->where('season_id', $seasonId))
            ->groupBy('author_id');

        return $this->buildListedCreatorsBaseQuery(null, $seasonId)
            ->addSelect('dungeon_routes_stats.dungeon_route_count', 'dungeon_routes_stats.dungeon_popularity')
            ->joinSub($dungeonRouteStats, 'dungeon_routes_stats', 'dungeon_routes_stats.author_id', '=', 'users.id')
            ->orderByDesc('dungeon_routes_stats.dungeon_popularity')
            ->orderByDesc('dungeon_routes_stats.dungeon_route_count')
            ->orderBy('users.id');
    }

    public function getCreatorStatsAttributes(int $userId, ?int $seasonId): array
    {
        $row = $this->buildPublishedRouteStatsQuery($seasonId)
            ->where('author_id', $userId)
            ->toBase()
            ->first();

        return $row === null ? [] : (array)$row;
    }

    public function isSlugTaken(string $slug, ?int $exceptUserId = null): bool
    {
        return User::query()
            ->where('slug', $slug)
            ->when($exceptUserId !== null, static fn(Builder $builder): Builder => $builder->whereKeyNot($exceptUserId))
            ->exists();
    }

    /** @return Builder<User> */
    private function buildListedCreatorsBaseQuery(?int $categoryId, ?int $seasonId): Builder
    {
        $minPublishedRoutes = (int)config('keystoneguru.creators.min_published_routes');
        $worldPublishedId   = PublishedState::ALL[PublishedState::WORLD];

        $publishedRouteStats = $this->buildPublishedRouteStatsQuery($seasonId)
            ->havingRaw('COUNT(*) >= ?', [$minPublishedRoutes]);

        return User::query()
            ->select(
                'users.*',
                'published_routes.published_route_count',
                'published_routes.total_views',
                'published_routes.season_route_count',
                'published_routes.season_views',
                'published_routes.season_popularity',
                'published_routes.rating_weighted_sum',
                'published_routes.rating_count',
                'published_routes.last_published_at',
            )
            ->joinSub($publishedRouteStats, 'published_routes', 'published_routes.author_id', '=', 'users.id')
            ->where('users.hide_from_creator_directory', false)
            // Filtering on a category asks "does this creator publicly share a collection of this
            // kind" - so only world published collections count. An unpublished or link-only
            // collection must never put its author in a filtered listing, since that would leak
            // that the collection exists at all
            ->when(
                $categoryId !== null,
                static fn(Builder $builder): Builder => $builder->whereHas(
                    'dungeonRouteCollections',
                    static fn(Builder $collectionBuilder): Builder => $collectionBuilder
                        ->where('dungeon_route_collection_category_id', $categoryId)
                        ->where('published_state_id', $worldPublishedId),
                ),
            )
            // The creator cards render the avatar
            ->with(['iconfile']);
    }

    /**
     * Every figure a creator is judged by, from one pass over their world-published routes. The
     * season columns are conditional sums rather than a second derived table, so adding them does
     * not add a second scan. Without a season they are constant zeroes - `season_id = NULL` would
     * never match, and `<=>` would count the routes that have no season instead.
     *
     * @return Builder<DungeonRoute>
     */
    private function buildPublishedRouteStatsQuery(?int $seasonId): Builder
    {
        $builder = DungeonRoute::query()
            ->selectRaw('author_id')
            ->selectRaw('COUNT(*) AS published_route_count')
            ->selectRaw('SUM(views) AS total_views')
            ->selectRaw('SUM(rating * rating_count) AS rating_weighted_sum')
            ->selectRaw('SUM(rating_count) AS rating_count')
            ->selectRaw('MAX(published_at) AS last_published_at')
            ->where('published_state_id', PublishedState::ALL[PublishedState::WORLD])
            ->groupBy('author_id');

        if ($seasonId === null) {
            return $builder->selectRaw('0 AS season_route_count, 0 AS season_views, 0 AS season_popularity');
        }

        return $builder
            ->selectRaw('SUM(season_id = ?) AS season_route_count', [$seasonId])
            ->selectRaw('SUM(IF(season_id = ?, views, 0)) AS season_views', [$seasonId])
            ->selectRaw('SUM(IF(season_id = ?, popularity, 0)) AS season_popularity', [$seasonId]);
    }
}
