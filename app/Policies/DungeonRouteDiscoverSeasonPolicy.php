<?php

namespace App\Policies;

use App\Models\Season;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DungeonRouteDiscoverSeasonPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the dungeon.
     */
    public function view(?User $user, Season $season): bool
    {
        return $season->expansion->active;
    }
}
