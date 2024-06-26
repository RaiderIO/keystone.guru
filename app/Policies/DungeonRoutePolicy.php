<?php

namespace App\Policies;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class DungeonRoutePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the dungeon route.
     */
    public function view(?User $user, DungeonRoute $dungeonroute): Response
    {
        // Everyone can view dungeon routes (for now)
        if (!$dungeonroute->mayUserView($user)) {
            return $this->deny(__('policy.view_route_not_published'));
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can present the dungeon route.
     */
    public function present(?User $user, DungeonRoute $dungeonroute): Response
    {
        // Everyone can view dungeon routes (for now)
        if (!$dungeonroute->mayUserView($user)) {
            return $this->deny(__('policy.present_route_not_published'));
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can preview the dungeon route.
     */
    public function preview(?User $user, DungeonRoute $dungeonroute, string $secret): bool
    {
        return config('keystoneguru.thumbnail.preview_secret') === $secret || ($user !== null && $user->is_admin);
    }

    /**
     * Determine whether the user can view an embedded the dungeon route.
     */
    public function embed(?User $user, DungeonRoute $dungeonroute): Response
    {
        // Everyone can view dungeon routes (for now)
        if (!$dungeonroute->mayUserView($user)) {
            return $this->deny(__('policy.embed_route_not_published'));
        }

        if ($dungeonroute->isSandbox()) {
            return $this->deny(__('policy.embed_route_sandbox_not_allowed'));
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can publish dungeon routes.
     */
    public function publish(User $user, DungeonRoute $dungeonroute): Response
    {
        if (!$dungeonroute->hasKilledAllRequiredEnemies()) {
            return $this->deny(__('policy.publish_not_all_required_enemies_killed'));
        }

        // Only authors or if the user is an admin
        return ($dungeonroute->isOwnedByUser($user) || $user->hasRole(Role::ROLE_ADMIN)) ?
            $this->allow() :
            $this->deny();
    }

    /**
     * Determine whether the user can unpublish dungeon routes.
     */
    public function unpublish(User $user, DungeonRoute $dungeonroute): bool
    {
        // Only authors or if the user is an admin
        return $dungeonroute->isOwnedByUser($user) || $user->hasRole(Role::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can rate a dungeon route.
     */
    public function rate(User $user, DungeonRoute $dungeonroute): bool
    {
        return !$dungeonroute->isOwnedByUser();
    }

    /**
     * Determine whether the user can favorite a dungeon route.
     */
    public function favorite(User $user, DungeonRoute $dungeonroute): bool
    {
        // All users may favorite all routes
        return true;
    }

    /**
     * Determine whether the user can clone a dungeon route.
     */
    public function clone(User $user, DungeonRoute $dungeonroute): bool
    {
        return $dungeonroute->mayUserView($user) || $dungeonroute->isOwnedByUser($user) || $user->hasRole(Role::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can migrate a dungeon route.
     */
    public function migrate(User $user, DungeonRoute $dungeonroute): bool
    {
        return $dungeonroute->mayUserEdit($user);
    }

    /**
     * Determine whether the user can update the dungeon route.
     */
    public function edit(?User $user, DungeonRoute $dungeonroute): bool
    {
        return $dungeonroute->mayUserEdit($user);
    }

    /**
     * Determine whether the user can delete the dungeon route.
     */
    public function delete(User $user, DungeonRoute $dungeonroute): bool
    {
        // Only the admin may delete routes
        return $dungeonroute->isOwnedByUser($user) || $user->hasRole(Role::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can restore the dungeon route.
     */
    public function restore(User $user, DungeonRoute $dungeonroute): bool
    {
        // Only authors or if the user is an admin
        return $dungeonroute->isOwnedByUser($user) || $user->hasRole(Role::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can permanently delete the dungeon route.
     */
    public function forceDelete(User $user, DungeonRoute $dungeonroute): bool
    {
        return $user->hasRole(Role::ROLE_ADMIN);
    }

    public function addKillZone(?User $user, DungeonRoute $dungeonRoute): Response
    {
        if ($dungeonRoute->killZones()->count() >= config('keystoneguru.dungeon_route_limits.kill_zones')) {
            return $this->deny(__('policy.add_kill_zone_limit_reached', ['limit' => config('keystoneguru.dungeon_route_limits.kill_zones')]));
        }

        return $this->allow();
    }

    public function addBrushline(?User $user, DungeonRoute $dungeonRoute): Response
    {
        if ($dungeonRoute->brushlines()->count() >= config('keystoneguru.dungeon_route_limits.brushlines')) {
            return $this->deny(__('policy.add_brushline_limit_reached', ['limit' => config('keystoneguru.dungeon_route_limits.brushlines')]));
        }

        return $this->allow();
    }

    public function addPath(?User $user, DungeonRoute $dungeonRoute): Response
    {
        if ($dungeonRoute->paths()->count() >= config('keystoneguru.dungeon_route_limits.paths')) {
            return $this->deny(__('policy.add_path_limit_reached', ['limit' => config('keystoneguru.dungeon_route_limits.paths')]));
        }

        return $this->allow();
    }

    public function addMapIcon(?User $user, DungeonRoute $dungeonRoute): Response
    {
        if ($dungeonRoute->mapicons()->count() >= config('keystoneguru.dungeon_route_limits.map_icons')) {
            return $this->deny(__('policy.add_map_icon_limit_reached', ['limit' => config('keystoneguru.dungeon_route_limits.map_icons')]));
        }

        return $this->allow();
    }
}
