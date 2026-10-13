<?php

namespace App\Repositories\Interfaces\Tags;

use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\User;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
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

    /**
     * The model a tag of the category is placed on: routes are addressed by their public key, every
     * other taggable by its id.
     */
    public function findTaggableModelOrFail(TagCategory $tagCategory, int|string $modelId): Model;

    /**
     * The first tag with the same name, under the same context, with the same category.
     */
    public function findFirstWithNameInContext(string $name, int|string $contextId, string $contextClass, int $tagCategoryId): ?Tag;

    /**
     * Renames and recolours every tag sharing the given tag's name, category and context.
     *
     * @return int The number of tags updated
     */
    public function updateAllLikeTag(Tag $tag, string $name, ?string $color): int;

    /**
     * Deletes every tag sharing the given tag's name, category and context.
     *
     * @return int The number of tags deleted
     */
    public function deleteAllLikeTag(Tag $tag): int;
}
