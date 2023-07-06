<?php

namespace App\Policies;

use App\Models\LiveSession;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LiveSessionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create a tag.
     *
     * @param User $user
     * @param LiveSession $liveSession
     * @return bool
     */
    public function view(User $user, LiveSession $liveSession)
    {
        return !$liveSession->isExpired();
    }
}
