<?php

namespace App\Policies;

use App\Models\Expansion;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DungeonRouteDiscoverExpansionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the dungeon.
     *
     * @param User|null $user
     * @param Expansion $expansion
     * @return mixed
     */
    public function view(?User $user, Expansion $expansion)
    {
        return $expansion->active;
    }
}
