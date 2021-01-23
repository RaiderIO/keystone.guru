<?php

namespace App\Policies;

use App\Models\DungeonRoute;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

class TagPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can edit the tag.
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
            case TagCategory::DUNGEON_ROUTE:
                /** @var DungeonRoute $model */
                $result = $model->mayUserEdit($user);
                break;
        }

        return $result;
    }

    /**
     * Determine whether the user can edit the tag.
     *
     * @param User $user
     * @param Tag $tag
     * @return mixed
     */
    public function edit(User $user, Tag $tag)
    {
        $result = false;

        if ($tag->model_id !== null) {
            switch ($tag->tagcategory->name) {
                case TagCategory::DUNGEON_ROUTE:
                    /** @var DungeonRoute $dungeonRoute */
                    $dungeonRoute = $tag->model;

                    $result = $dungeonRoute->mayUserEdit($user);
                    break;
            }
        } else {
            // If the tag is not directly assigned to a model, it was created through the tag manager and this check should suffice
            $result = $tag->user_id === $user->id;
        }

        return $result;
    }

    /**
     * @param User $user
     * @param Tag $tag
     * @return bool|mixed
     */
    public function delete(User $user, Tag $tag)
    {
        return $this->edit($user, $tag);
    }
}
