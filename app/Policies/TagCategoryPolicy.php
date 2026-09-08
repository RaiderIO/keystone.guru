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
     * Each category lives in exactly one kind of context, and the two are checked together: the
     * surfaces that read tags back - DungeonRoute::tagspersonal()/tagsteam() and the route listing's
     * tag filter - select on the category alone, so a personal tag created in a team's context is
     * served to everyone reading that route's personal tags, and the other way around.
     *
     * @param Model $context The user or team the tag is created in.
     */
    public function createTag(User $user, TagCategory $tagCategory, Model $model, Model $context): bool
    {
        $contextIsOwnedAndPairedWithCategory = match ($tagCategory->name) {
            TagCategory::DUNGEON_ROUTE_PERSONAL => $context instanceof User && $context->is($user),
            TagCategory::DUNGEON_ROUTE_TEAM     => $context instanceof Team && $context->isUserMember($user),
            default                             => false,
        };

        return $contextIsOwnedAndPairedWithCategory && $model instanceof DungeonRoute && $model->mayUserEdit($user);
    }
}
