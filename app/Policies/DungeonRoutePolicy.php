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
     * @param User|null    $user
     * @param DungeonRoute $dungeonroute
     * @return mixed
     */
    public function view(?User $user, DungeonRoute $dungeonroute)
    {
        // Everyone can view dungeon routes (for now)
        if (!$dungeonroute->mayUserView($user)) {
            return $this->deny(__('policy.view_route_not_published'));
        }

        return true;
    }

    /**
     * Determine whether the user can present the dungeon route.
     *
     * @param User|null    $user
     * @param DungeonRoute $dungeonroute
     * @return mixed
     */
    public function present(?User $user, DungeonRoute $dungeonroute)
    {
        // Everyone can view dungeon routes (for now)
        if (!$dungeonroute->mayUserView($user)) {
            return $this->deny(__('policy.present_route_not_published'));
        }

        return true;
    }

    /**
     * Determine whether the user can view the dungeon route.
     *
     * @param User|null    $user
     * @param DungeonRoute $dungeonroute
     * @param string       $secret
     * @return mixed
     */
    public function preview(?User $user, DungeonRoute $dungeonroute, string $secret)
    {
        return config('keystoneguru.thumbnail.preview_secret') === $secret || ($user !== null && $user->is_admin);
    }

    /**
     * Determine whether the user can view an embedded the dungeon route.
     *
     * @param User|null    $user
     * @param DungeonRoute $dungeonroute
     * @return mixed
     */
    public function embed(?User $user, DungeonRoute $dungeonroute)
    {
        // Everyone can view dungeon routes (for now)
        if (!$dungeonroute->mayUserView($user)) {
            return $this->deny(__('policy.embed_route_not_published'));
        }

        if ($dungeonroute->isSandbox()) {
            return $this->deny(__('policy.embed_route_sandbox_not_allowed'));
        }

        return true;
    }

    /**
     * Determine whether the user can publish dungeon routes.
     *
     * @param User         $user
     * @param DungeonRoute $dungeonroute
     * @return mixed
     */
    public function publish(User $user, DungeonRoute $dungeonroute)
    {
        if (!$dungeonroute->hasKilledAllRequiredEnemies()) {
            return $this->deny(__('policy.publish_not_all_required_enemies_killed'));
        }

        // Only authors or if the user is an admin
        return ($dungeonroute->isOwnedByUser($user) || $user->hasRole('admin'));
    }

    /**
     * Determine whether the user can unpublish dungeon routes.
     *
     * @param User         $user
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
     * @param User         $user
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
     * @param User         $user
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
     * @param User         $user
     * @param DungeonRoute $dungeonroute
     * @return mixed
     */
    public function clone(User $user, DungeonRoute $dungeonroute)
    {
        return $dungeonroute->mayUserView($user) || $dungeonroute->isOwnedByUser($user) || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can migrate a dungeon route.
     *
     * @param User         $user
     * @param DungeonRoute $dungeonroute
     * @return mixed
     */
    public function migrate(User $user, DungeonRoute $dungeonroute)
    {
        return $dungeonroute->mayUserEdit($user);
    }

    /**
     * Determine whether the user can update the dungeon route.
     *
     * @param User|null    $user
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
     * @param User         $user
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
     * @param User         $user
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
     * @param User         $user
     * @param DungeonRoute $dungeonroute
     * @return mixed
     */
    public function forceDelete(User $user, DungeonRoute $dungeonroute)
    {
        return $user->hasRole('admin');
    }

    /**
     * @param User|null    $user
     * @param DungeonRoute $dungeonRoute
     * @return mixed
     */
    public function addKillZone(?User $user, DungeonRoute $dungeonRoute)
    {
        if ($dungeonRoute->killZones()->count() >= config('keystoneguru.dungeon_route_limits.kill_zones')) {
            return $this->deny(__('policy.add_kill_zone_limit_reached', ['limit' => config('keystoneguru.dungeon_route_limits.kill_zones')]));
        }

        return true;
    }

    /**
     * @param User|null    $user
     * @param DungeonRoute $dungeonRoute
     * @return mixed
     */
    public function addBrushline(?User $user, DungeonRoute $dungeonRoute)
    {
        if ($dungeonRoute->brushlines()->count() >= config('keystoneguru.dungeon_route_limits.brushlines')) {
            return $this->deny(__('policy.add_brushline_limit_reached', ['limit' => config('keystoneguru.dungeon_route_limits.brushlines')]));
        }

        return true;
    }

    /**
     * @param User|null    $user
     * @param DungeonRoute $dungeonRoute
     * @return mixed
     */
    public function addPath(?User $user, DungeonRoute $dungeonRoute)
    {
        if ($dungeonRoute->paths()->count() >= config('keystoneguru.dungeon_route_limits.paths')) {
            return $this->deny(__('policy.add_path_limit_reached', ['limit' => config('keystoneguru.dungeon_route_limits.paths')]));
        }

        return true;
    }

    /**
     * @param User|null    $user
     * @param DungeonRoute $dungeonRoute
     * @return mixed
     */
    public function addMapIcon(?User $user, DungeonRoute $dungeonRoute)
    {
        if ($dungeonRoute->mapicons()->count() >= config('keystoneguru.dungeon_route_limits.map_icons')) {
            return $this->deny(__('policy.add_map_icon_limit_reached', ['limit' => config('keystoneguru.dungeon_route_limits.map_icons')]));
        }

        return true;
    }
}
