<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use App\Repositories\BaseRepositoryInterface;
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
     * Creators eligible for the creator directory, ordered by how many routes they have published.
     *
     * Listing is automatic above a threshold and opt-out, so the only people excluded are those
     * below the bar and those who ticked hide_from_creator_directory.
     *
     * The threshold and the count rendered on each card come from the same aggregate (exposed as
     * `published_route_count` on the returned models), so they cannot drift apart. Results are
     * ordered by that count descending, with `users.id` as a stable tiebreak so pagination cannot
     * repeat or skip a creator between pages.
     *
     * @return Builder<User>
     */
    public function buildListedCreatorsQuery(): Builder;
}
