<?php

namespace App\Repositories\Database;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
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
     * @return Builder<User>
     */
    public function buildListedCreatorsQuery(): Builder
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
            // The creator cards render the avatar
            ->with(['iconfile'])
            ->orderByDesc('published_routes.published_route_count')
            // Stable tiebreak so pagination cannot repeat or skip a creator between pages
            ->orderBy('users.id');
    }
}
