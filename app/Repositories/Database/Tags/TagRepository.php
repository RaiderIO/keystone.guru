<?php

namespace App\Repositories\Database\Tags;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\User;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Tags\TagRepositoryInterface;
use Illuminate\Support\Collection;

class TagRepository extends DatabaseRepository implements TagRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Tag::class);
    }

    public function getPersonalRouteTagNames(User $user): Collection
    {
        return Tag::query()
            ->where('context_id', $user->id)
            ->where('context_class', User::class)
            ->where('tag_category_id', TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL])
            ->where('model_class', DungeonRoute::class)
            ->whereNotNull('model_id')
            ->distinct()
            ->orderBy('name')
            ->pluck('name');
    }
}
