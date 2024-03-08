<?php

namespace App\Policies;

use App\Models\Tags\TagCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

class TagCategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create a tag.
     */
    public function createTag(User $user, TagCategory $tagCategory, Model $model): bool
    {
        $result = match ($tagCategory->name) {
            TagCategory::DUNGEON_ROUTE_PERSONAL, TagCategory::DUNGEON_ROUTE_TEAM => $model->mayUserEdit($user),
            default => false,
        };

        return $result;
    }
}
