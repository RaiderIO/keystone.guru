<?php

namespace App\Policies;

use App\Models\Expansion;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DungeonRouteDiscoverExpansionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the expansion.
     */
    public function view(?User $user, Expansion $expansion): bool
    {
        return $expansion->active;
    }
}
