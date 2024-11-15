<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeamPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can edit a team.
     */
    public function edit(User $user, Team $team): bool
    {
        // Everyone can view dungeon routes (for now)
        return $team->isUserMember($user);
    }

    /**
     * Determine whether the user can delete a team or not.
     */
    public function delete(User $user, Team $team): bool
    {
        return $team->isUserMember($user) && $team->getUserRole($user) === TeamUser::ROLE_ADMIN;
    }

    public function moderateRoute(User $user, Team $team): bool
    {
        return $team->canAddRemoveRoute($user);
    }

    public function changeRole(User $user, Team $team): bool
    {
        return $team->isUserModerator($user);
    }

    public function changeDefaultRole(User $user, Team $team): bool
    {
        return $team->getUserRole($user) === TeamUser::ROLE_ADMIN;
    }

    public function changeRoutePublishing(User $user, Team $team): bool
    {
        return $team->getUserRole($user) === TeamUser::ROLE_ADMIN;
    }

    public function removeMember(User $user, Team $team, User $member): bool
    {
        return $team->canRemoveMember($user, $member);
    }

    public function refreshInviteLink(User $user, Team $team): bool
    {
        return $team->isUserModerator($user);
    }

    /**
     * Determine whether the user can perform ad-free giveaways on a member of a team.
     */
    public function canAdFreeGiveaway(User $user, Team $team): bool
    {
        // Everyone can view dungeon routes (for now)
        return $team->isUserMember($user);
    }
}
