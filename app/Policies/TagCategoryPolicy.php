<?php

namespace App\Policies;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Tags\TagCategory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

class TagCategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create a tag.
     *
     * @param Model $context The user or team the tag is created in.
     */
    public function createTag(User $user, TagCategory $tagCategory, Model $model, Model $context): bool
    {
        // The context must belong to the user: their own account, or a team they're a member of
        $contextBelongsToUser = match (true) {
            $context instanceof User => $context->is($user),
            $context instanceof Team => $context->isUserMember($user),
            default                  => false,
        };

        $result = $contextBelongsToUser && match ($tagCategory->name) {
            TagCategory::DUNGEON_ROUTE_PERSONAL, TagCategory::DUNGEON_ROUTE_TEAM => $model instanceof DungeonRoute && $model->mayUserEdit($user),
            default                                                              => false,
        };

        return $result;
    }
}
