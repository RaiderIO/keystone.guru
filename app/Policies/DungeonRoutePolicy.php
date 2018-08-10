<?php

namespace App\Policies;

use App\User;
use App\Models\DungeonRoute;
use Illuminate\Auth\Access\HandlesAuthorization;

class DungeonRoutePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the dungeon route.
     *
     * @param  \App\User $user
     * @param  \App\Models\DungeonRoute $dungeonRoute
     * @return mixed
     */
    public function view()
    {
        // Everyone can view dungeon routes (for now)
        return true;
    }

    /**
     * Determine whether the user can create dungeon routes.
     *
     * @param  \App\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        // All users may create a dungeon route (non-anons)
        return true;
    }

    /**
     * Determine whether the user can update the dungeon route.
     *
     * @param  \App\User $user
     * @param  \App\Models\DungeonRoute $dungeonroute
     * @return mixed
     */
    public function edit(User $user, DungeonRoute $dungeonroute)
    {
        // Only authors or if the user is an admin
        return $user->id === $dungeonroute->author_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the dungeon route.
     *
     * @param  \App\User $user
     * @param  \App\Models\DungeonRoute $dungeonRoute
     * @return mixed
     */
    public function delete(User $user, DungeonRoute $dungeonRoute)
    {
        // Only the admin may delete routes
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the dungeon route.
     *
     * @param  \App\User $user
     * @param  \App\Models\DungeonRoute $dungeonRoute
     * @return mixed
     */
    public function restore(User $user, DungeonRoute $dungeonRoute)
    {
        // Only authors or if the user is an admin
        return $user->id === $dungeonRoute->author_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the dungeon route.
     *
     * @param  \App\User $user
     * @param  \App\Models\DungeonRoute $dungeonRoute
     * @return mixed
     */
    public function forceDelete(User $user, DungeonRoute $dungeonRoute)
    {
        //
        return $user->hasRole('admin');
    }
}
