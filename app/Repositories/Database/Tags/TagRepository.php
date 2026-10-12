<?php

namespace App\Repositories\Database\Tags;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\User;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Tags\TagRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class TagRepository extends DatabaseRepository implements TagRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Tag::class);
    }

    public function getPersonalRouteTags(User $user): Collection
    {
        $firstTagIdPerName = Tag::query()
            ->selectRaw('MIN(id)')
            ->where('context_id', $user->id)
            ->where('context_class', User::class)
            ->where('tag_category_id', TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL])
            ->where('model_class', DungeonRoute::class)
            ->whereNotNull('model_id')
            ->groupBy('name');

        return Tag::query()
            ->whereIn('id', $firstTagIdPerName)
            ->orderBy('name')
            ->get();
    }

    public function findTaggableModelOrFail(TagCategory $tagCategory, int|string $modelId): Model
    {
        /** @var Builder<Model> $query */
        $query = $tagCategory->model_class::query();
        if (in_array($tagCategory->key, [
            TagCategory::DUNGEON_ROUTE_PERSONAL,
            TagCategory::DUNGEON_ROUTE_TEAM,
        ])) {
            $query = $query->where('public_key', $modelId);
        } else {
            $query = $query->where('id', $modelId);
        }

        return $query->firstOrFail();
    }

    public function findFirstWithNameInContext(string $name, int|string $contextId, string $contextClass, int $tagCategoryId): ?Tag
    {
        return Tag::where('name', $name)
            ->where('context_id', $contextId)
            ->where('context_class', $contextClass)
            ->where('tag_category_id', $tagCategoryId)
            ->first();
    }

    public function updateAllLikeTag(Tag $tag, string $name, ?string $color): int
    {
        return Tag::where('name', $tag->name)
            ->where('tag_category_id', $tag->tag_category_id)
            ->where('context_id', $tag->context_id)
            ->where('context_class', $tag->context_class)
            ->update([
                'name'  => $name,
                'color' => $color,
            ]);
    }

    public function deleteAllLikeTag(Tag $tag): int
    {
        return Tag::where('name', $tag->name)
            ->where('tag_category_id', $tag->tag_category_id)
            ->where('context_id', $tag->context_id)
            ->where('context_class', $tag->context_class)
            ->delete();
    }

    public function existsPersonalRouteTagNamed(User $user, string $name): bool
    {
        return Tag::where('name', $name)
            ->where('context_id', $user->id)
            ->where('context_class', User::class)
            ->where('tag_category_id', TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL])
            ->exists();
    }
}
