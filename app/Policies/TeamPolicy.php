<?php

namespace App\Policies;

use App\Models\Team;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeamPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can edit a team.
     *
     * @param  \App\User $user
     * @param  \App\Models\Team $team
     * @return bool
     */
    public function edit(User $user, Team $team)
    {
        // Everyone can view dungeon routes (for now)
        return $team->isUserMember($user);
    }

    /**
     * Determine whether the user can delete a team or not.
     *
     * @param User $user
     * @param Team $team
     * @return bool
     */
    public function delete(User $user, Team $team)
    {
        return $team->isUserMember($user) && $team->getUserRole($user) === 'admin';
    }

    /**
     * @param User $user
     * @param Team $team
     * @return boolean
     */
    public function moderateRoute(User $user, Team $team)
    {
        return $team->canAddRemoveRoute($user);
    }

    /**
     * @param User $user
     * @param Team $team
     * @return boolean
     */
    public function changeRole(User $user, Team $team)
    {
        return $team->isUserModerator($user);
    }

    /**
     * @param User $user
     * @param Team $team
     * @return boolean
     */
    public function removeMember(User $user, Team $team)
    {
        return $team->isUserModerator($user);
    }
}
