<?php

namespace App\Policies;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TagPolicy
{
    use HandlesAuthorization;

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
            // If we're editing a team tag, and the user is part of this team, we can edit it.
            // find() rather than findOrFail(): a team tag can outlive its team. Team::removeMember()
            // and the team's deleting hook now clean up a leaving/removed member's team tags going
            // forward (#3866), but rows orphaned before that fix shipped can still have a
            // context_id that dangles, and findOrFail() would 404 every caller instead of denying
            // them.
            $result = Team::find($tag->context_id)?->isUserMember($user) ?? false;
        }

        // Falling back to the tagged route's own permissions keeps a tag whose team membership no
        // longer holds - or whose team is gone entirely - manageable by the people who own the route
        // it is stuck on, rather than leaving it undeletable by anyone
        if (!$result && $tag->model_id !== null) {
            switch ($tag->tagCategory->name) {
                case TagCategory::DUNGEON_ROUTE_PERSONAL:
                case TagCategory::DUNGEON_ROUTE_TEAM:
                    /** @var DungeonRoute|null $dungeonRoute */
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
