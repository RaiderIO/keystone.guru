<?php

namespace App\Policies;

use App\Models\LiveSession;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LiveSessionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create a tag.
     */
    public function view(User $user, LiveSession $liveSession): bool
    {
        return ! $liveSession->isExpired();
    }
}
