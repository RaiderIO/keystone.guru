<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use App\Repositories\BaseRepositoryInterface;
use App\Service\Creator\Enums\CreatorDirectorySort;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * @method User                  create(array<string, mixed> $attributes)
 * @method User|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method User                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method User                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                  save(User $model)
 * @method bool                  update(User $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                  delete(User $model)
 * @method Collection<int, User> all()
 * @method bool                  exists(array<int, string> $columns)
 */
interface UserRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Creators eligible for the creator directory.
     *
     * Listing is automatic above a threshold and opt-out, so the only people excluded are those
     * below the bar and those who ticked hide_from_creator_directory.
     *
     * The threshold and the figures rendered on each card come from the same aggregate, exposed on
     * the returned models as `published_route_count`, `total_views`, `season_route_count`,
     * `season_views`, `season_popularity`, `rating_weighted_sum`, `rating_count` and
     * `last_published_at`, so they cannot drift apart. `users.id` is always the final tiebreak so
     * pagination cannot repeat or skip a creator between pages.
     *
     * @param int|null             $categoryId When set, only creators who publicly share a collection
     *                                         filed under this category are listed.
     * @param int|null             $seasonId   The season the season_* figures are counted for; they are
     *                                         zero without one.
     * @param CreatorDirectorySort $sort       ActiveThisSeason puts creators with routes in $seasonId
     *                                         first, by their season popularity; it falls back to
     *                                         MostRoutes without a season.
     *
     * @return Builder<User>
     */
    public function buildListedCreatorsQuery(
        ?int                 $categoryId = null,
        ?int                 $seasonId = null,
        CreatorDirectorySort $sort = CreatorDirectorySort::MostRoutes,
    ): Builder;

    /**
     * Listed creators who have world-published routes for a dungeon, most popular there first.
     * Exposes `dungeon_route_count` and `dungeon_popularity` next to the listed-creator figures.
     *
     * @param int|null $seasonId When set, only the dungeon's routes of this season count.
     *
     * @return Builder<User>
     */
    public function buildFeaturedCreatorsForDungeonQuery(int $dungeonId, ?int $seasonId): Builder;

    /**
     * The same aggregate buildListedCreatorsQuery() lists creators by, for one user regardless of
     * whether they are listed. Empty when the user has no world-published routes.
     *
     * @return array<string, mixed>
     */
    public function getCreatorStatsAttributes(int $userId, ?int $seasonId): array;

    /**
     * Whether a user other than $exceptUserId holds this slug. Compared under the column's
     * collation, the same one its unique index enforces.
     */
    public function isSlugTaken(string $slug, ?int $exceptUserId = null): bool;
}
