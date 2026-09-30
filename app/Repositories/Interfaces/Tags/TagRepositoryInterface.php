<?php

namespace App\Repositories\Interfaces\Tags;

use App\Models\Tags\Tag;
use App\Models\User;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Tag                  create(array<string, mixed> $attributes)
 * @method Tag|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method Tag                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method Tag                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                 save(Tag $model)
 * @method bool                 update(Tag $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                 delete(Tag $model)
 * @method Collection<int, Tag> all()
 * @method bool                 exists(array<int, string> $columns)
 */
interface TagRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * The user's personal route tags that sit on at least one route, one per name, alphabetically.
     *
     * @return Collection<int, Tag>
     */
    public function getPersonalRouteTags(User $user): Collection;
}
