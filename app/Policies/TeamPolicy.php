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
     * @return mixed
     */
    public function edit(User $user, Team $team)
    {
        // Everyone can view dungeon routes (for now)
        return $team->isUserMember($user);
    }
}
