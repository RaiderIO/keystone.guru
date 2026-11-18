<?php

namespace App\Policies;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeamPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view a team's page. Site admins may open any team to help its members.
     */
    public function edit(User $user, Team $team): bool
    {
        return $team->isUserMember($user) || $user->hasRole(Role::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can change a team's details (description, logo). Site admins are
     * included, as the edit page offers them the details tab.
     */
    public function update(User $user, Team $team): bool
    {
        return $team->isUserModerator($user) || $user->hasRole(Role::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can delete a team or not: the team's admin, or a site admin.
     */
    public function delete(User $user, Team $team): bool
    {
        return $team->isUserAdmin($user) || $user->hasRole(Role::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can add routes to a team.
     */
    public function addRoute(User $user, Team $team): bool
    {
        return $team->canAddRoute($user);
    }

    /**
     * Determine whether the user can take a route out of a team.
     */
    public function removeRoute(User $user, Team $team, DungeonRoute $dungeonRoute): bool
    {
        return $team->canRemoveRoute($user, $dungeonRoute);
    }

    /**
     * Determine whether the user can change $targetUser's role in the team into $role.
     * Being a moderator is not enough on its own - canChangeRole() additionally enforces the rank
     * ordering, so nobody can promote past their own rank or demote someone above them. Site admins
     * are outside the ranks and may change anyone, short of demoting the team's last admin.
     */
    public function changeRole(User $user, Team $team, User $targetUser, string $role): bool
    {
        return ($team->isUserModerator($user) || $user->hasRole(Role::ROLE_ADMIN)) && $team->canChangeRole($user, $targetUser, $role);
    }

    public function changeDefaultRole(User $user, Team $team): bool
    {
        return $team->isUserAdmin($user) || $user->hasRole(Role::ROLE_ADMIN);
    }

    public function removeMember(User $user, Team $team, User $member): bool
    {
        return $team->canRemoveMember($user, $member);
    }

    public function refreshInviteLink(User $user, Team $team): bool
    {
        return $team->isUserModerator($user) || $user->hasRole(Role::ROLE_ADMIN);
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
