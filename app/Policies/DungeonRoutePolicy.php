<?php

namespace App\Policies;

use App\Models\DungeonRoute;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DungeonRoutePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the dungeon route.
     *
     * @param User|null $user
     * @param DungeonRoute $dungeonroute
     * @return mixed
     */
    public function view(?User $user, DungeonRoute $dungeonroute)
    {
        // Everyone can view dungeon routes (for now)
        if (!$dungeonroute->mayUserView($user)) {
            return $this->deny('This route is not published and cannot be viewed. Please ask the author to publish this route to view it.');
        }

        return true;
    }

    /**
     * Determine whether the user can view an embedded the dungeon route.
     *
     * @param User|null $user
     * @param DungeonRoute $dungeonroute
     * @return mixed
     */
    public function embed(?User $user, DungeonRoute $dungeonroute)
    {
        // Everyone can view dungeon routes (for now)
        if (!$dungeonroute->mayUserView($user)) {
            return $this->deny('This route is not published and cannot be viewed. Please ask the author to publish this route to view it.');
        }

        if($dungeonroute->isSandbox()){
            return $this->deny('Sandbox routes cannot be embedded.');
        }

        return true;
    }

    /**
     * Determine whether the user can publish dungeon routes.
     *
     * @param User $user
     * @param DungeonRoute $dungeonroute
     * @return mixed
     */
    public function publish(User $user, DungeonRoute $dungeonroute)
    {
        if (!$dungeonroute->hasKilledAllUnskippables()) {
            return $this->deny('Unable to change sharing settings: not all unskippable enemies have been killed');
        }
        // Only authors or if the user is an admin
        return ($dungeonroute->isOwnedByUser($user) || $user->hasRole('admin'));
    }

    /**
     * Determine whether the user can unpublish dungeon routes.
     *
     * @param User $user
     * @param DungeonRoute $dungeonroute
     * @return mixed
     */
    public function unpublish(User $user, DungeonRoute $dungeonroute)
    {
        // Only authors or if the user is an admin
        return ($dungeonroute->isOwnedByUser($user) || $user->hasRole('admin'));
    }

    /**
     * Determine whether the user can rate a dungeon route.
     *
     * @param User $user
     * @param DungeonRoute $dungeonroute
     * @return mixed
     */
    public function rate(User $user, DungeonRoute $dungeonroute)
    {
        return !$dungeonroute->isOwnedByUser();
    }

    /**
     * Determine whether the user can favorite a dungeon route.
     *
     * @param User $user
     * @param DungeonRoute $dungeonroute
     * @return mixed
     */
    public function favorite(User $user, DungeonRoute $dungeonroute)
    {
        // All users may favorite all routes
        return true;
    }

    /**
     * Determine whether the user can clone a dungeon route.
     *
     * @param User $user
     * @param DungeonRoute $dungeonroute
     * @return mixed
     */
    public function clone(User $user, DungeonRoute $dungeonroute)
    {
        return $dungeonroute->mayUserView($user) || $dungeonroute->isOwnedByUser($user) || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the dungeon route.
     *
     * @param User|null $user
     * @param DungeonRoute $dungeonroute
     * @return mixed
     */
    public function edit(?User $user, DungeonRoute $dungeonroute)
    {
        return $dungeonroute->mayUserEdit($user);
    }

    /**
     * Determine whether the user can delete the dungeon route.
     *
     * @param User $user
     * @param DungeonRoute $dungeonroute
     * @return mixed
     */
    public function delete(User $user, DungeonRoute $dungeonroute)
    {
        // Only the admin may delete routes
        return $dungeonroute->isOwnedByUser($user) || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the dungeon route.
     *
     * @param User $user
     * @param DungeonRoute $dungeonroute
     * @return mixed
     */
    public function restore(User $user, DungeonRoute $dungeonroute)
    {
        // Only authors or if the user is an admin
        return $dungeonroute->isOwnedByUser($user) || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the dungeon route.
     *
     * @param User $user
     * @param DungeonRoute $dungeonroute
     * @return mixed
     */
    public function forceDelete(User $user, DungeonRoute $dungeonroute)
    {
        //
        return $user->hasRole('admin');
    }
}
