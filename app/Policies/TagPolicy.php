<?php

namespace App\Policies;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

class TagPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can edit the tag.
     */
    public function createTag(User $user, TagCategory $tagCategory, Model $model): bool
    {
        $result = match ($tagCategory->name) {
            TagCategory::DUNGEON_ROUTE_PERSONAL, TagCategory::DUNGEON_ROUTE_TEAM => $model->mayUserEdit($user),
            default => false,
        };

        return $result;
    }

    /**
     * Determine whether the user can edit the tag.
     */
    public function edit(User $user, Tag $tag): bool
    {
        $result = false;

        if ($tag->model_id !== null) {
            switch ($tag->tagCategory->name) {
                case TagCategory::DUNGEON_ROUTE_PERSONAL:
                case TagCategory::DUNGEON_ROUTE_TEAM:
                    /** @var DungeonRoute $dungeonRoute */
                    $dungeonRoute = $tag->model;

                    $result = $dungeonRoute?->mayUserEdit($user) ?? false;
                    break;
            }
        } else {
            // If the tag is not directly assigned to a model, it was created through the tag manager and this check should suffice
            $result = $tag->user_id === $user->id;
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function delete(User $user, Tag $tag): bool
    {
        return $this->edit($user, $tag);
    }
}
