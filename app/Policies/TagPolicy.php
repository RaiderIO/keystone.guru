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
     *
     * @return mixed
     */
    public function createTag(User $user, TagCategory $tagCategory, Model $model)
    {
        $result = false;

        $result = match ($tagCategory->name) {
            TagCategory::DUNGEON_ROUTE_PERSONAL, TagCategory::DUNGEON_ROUTE_TEAM => $model->mayUserEdit($user),
            default => $result,
        };

        return $result;
    }

    /**
     * Determine whether the user can edit the tag.
     *
     * @return mixed
     */
    public function edit(User $user, Tag $tag)
    {
        $result = false;

        if ($tag->model_id !== null) {
            switch ($tag->tagCategory->name) {
                case TagCategory::DUNGEON_ROUTE_PERSONAL:
                case TagCategory::DUNGEON_ROUTE_TEAM:
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
     * @return bool|mixed
     */
    public function delete(User $user, Tag $tag)
    {
        return $this->edit($user, $tag);
    }
}
