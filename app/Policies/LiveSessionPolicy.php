<?php

namespace App\Policies;

use App\Models\Laratrust\Role;
use App\Models\LiveSession;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LiveSessionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the live session.
     */
    public function view(User $user, LiveSession $liveSession): bool
    {
        return !$liveSession->isExpired();
    }

    /**
     * Determine whether the user can end this live session. A live session may be started on any
     * dungeon route the starting user can view (not just their own), so only the session's own
     * creator - or an admin - may end it; the dungeon route's own edit permissions are irrelevant.
     */
    public function end(User $user, LiveSession $liveSession): bool
    {
        return $user->id === $liveSession->user_id || $user->hasRole(Role::ROLE_ADMIN);
    }
}
