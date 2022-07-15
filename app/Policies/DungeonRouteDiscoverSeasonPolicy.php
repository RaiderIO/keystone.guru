<?php

namespace App\Policies;

use App\Models\Season;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DungeonRouteDiscoverSeasonPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the dungeon.
     *
     * @param User|null $user
     * @param Season $season
     * @return mixed
     */
    public function view(?User $user, Season $season)
    {
        return $season->expansion->active;
    }
}
