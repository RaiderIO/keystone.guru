<?php

namespace App\Service\Creator;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CreatorDirectoryService implements CreatorDirectoryServiceInterface
{
    /** @return LengthAwarePaginator<int, User> */
    public function paginateCreators(?string $search = null, ?int $categoryId = null, ?int $perPage = null): LengthAwarePaginator
    {
        $perPage ??= (int)config('keystoneguru.creators.per_page');

        return $this->buildListedCreatorsQuery($categoryId)
            ->when(
                $search !== null && $search !== '',
                static fn(Builder $builder): Builder => $builder->where(
                    'users.name',
                    'like',
                    sprintf('%%%s%%', addcslashes((string)$search, '%_\\')),
                ),
            )
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @return Collection<int, User> */
    public function getFeaturedCreators(?int $limit = null): Collection
    {
        $limit ??= (int)config('keystoneguru.creators.featured_count');

        return $this->buildListedCreatorsQuery()
            ->limit($limit)
            ->get();
    }

    /**
     * Creators eligible for the directory, ordered by how many routes they have published.
     *
     * Listing is automatic above a threshold and opt-out, so the only people excluded are those
     * below the bar and those who ticked hide_from_creator_directory.
     *
     * The threshold and the count rendered on each card come from the same aggregate, so they
     * cannot drift apart.
     *
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
     * @param int|null $categoryId When set, only creators who publicly share a collection filed
     *                             under this category are listed.
     *
     * @return Builder<User>
     */
    private function buildListedCreatorsQuery(?int $categoryId = null): Builder
    {
        $minPublishedRoutes = (int)config('keystoneguru.creators.min_published_routes');
        $worldPublishedId   = PublishedState::ALL[PublishedState::WORLD];

        $publishedRouteCounts = DungeonRoute::query()
            ->selectRaw('author_id, COUNT(*) AS published_route_count')
            ->where('published_state_id', $worldPublishedId)
            ->groupBy('author_id')
            ->havingRaw('COUNT(*) >= ?', [$minPublishedRoutes]);

        return User::query()
            ->select('users.*', 'published_routes.published_route_count')
            ->joinSub($publishedRouteCounts, 'published_routes', 'published_routes.author_id', '=', 'users.id')
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
                        ->where('published_state_id', PublishedState::ALL[PublishedState::WORLD]),
                ),
            )
            // The creator cards render the avatar
            ->with(['iconfile'])
            ->orderByDesc('published_routes.published_route_count')
            // Stable tiebreak so pagination cannot repeat or skip a creator between pages
            ->orderBy('users.id');
    }
}
