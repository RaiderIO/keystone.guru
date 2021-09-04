<?php

namespace App\Policies;

use App\Models\DungeonRoute;
use App\Models\Tags\TagCategory;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

class TagCategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create a tag.
     *
     * @param User $user
     * @param TagCategory $tagCategory
     * @param Model $model
     * @return mixed
     */
    public function createTag(User $user, TagCategory $tagCategory, Model $model)
    {
        $result = false;

        switch ($tagCategory->name) {
            case TagCategory::DUNGEON_ROUTE_PERSONAL:
            case TagCategory::DUNGEON_ROUTE_TEAM:
                /** @var DungeonRoute $model */
                $result = $model->mayUserEdit($user);
                break;
        }

        return $result;
    }
}
