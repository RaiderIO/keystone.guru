<?php

namespace App\Policies;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\Team;
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

        // If the tag is from a specific user, you can only edit it if you're that user
        if ($tag->context_class === User::class && $tag->context_id === $user->id) {
            $result = true;
        } elseif ($tag->context_class === Team::class) {
            // If we're editing a team tag, and the user is part of this team, we can edit it
            if (Team::findOrFail($tag->context_id)->isUserMember($user)) {
                $result = true;
            }
        } elseif ($tag->model_id !== null) {
            switch ($tag->tagCategory->name) {
                case TagCategory::DUNGEON_ROUTE_PERSONAL:
                case TagCategory::DUNGEON_ROUTE_TEAM:
                    /** @var DungeonRoute $dungeonRoute */
                    $dungeonRoute = $tag->model;

                    $result = $dungeonRoute?->mayUserEdit($user) ?? false;
                    break;
            }
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
