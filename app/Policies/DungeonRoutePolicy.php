<?php

namespace App\Policies;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
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
     *
     * @param string|null $publishedState The state the route is being changed to, if known. Unpublishing is always
     *                                    allowed - the required enemies guard would otherwise trap an already
     *                                    published route in a state its author can no longer undo.
     */
    public function publish(User $user, DungeonRoute $dungeonroute, ?string $publishedState = null): Response
    {
        if ($publishedState !== PublishedState::UNPUBLISHED && !$dungeonroute->hasKilledAllRequiredEnemies()) {
            return $this->deny(__('policy.publish_not_all_required_enemies_killed'));
        }

        // Only authors or if the user is an admin
        return ($dungeonroute->isOwnedByUser($user) || $user->hasRole(Role::ROLE_ADMIN)) ?
            $this->allow() :
            $this->deny();
    }

    /**
     * Determine whether the user can rate a dungeon route.
     */
    public function rate(User $user, DungeonRoute $dungeonroute): bool
    {
        return !$dungeonroute->isOwnedByUser($user);
    }

    /**
     * Determine whether the user can clone a dungeon route.
     */
    public function clone(User $user, DungeonRoute $dungeonroute): bool
    {
        return $dungeonroute->mayUserView($user) || $dungeonroute->isOwnedByUser($user) || $user->hasRole(Role::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can claim a dungeon route as their own.
     * Only sandbox routes are unowned and therefore claimable.
     */
    public function claim(User $user, DungeonRoute $dungeonroute): Response
    {
        return $dungeonroute->isSandbox() ?
            $this->allow() :
            $this->deny(__('policy.claim_route_not_claimable'));
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
     * Determine whether the user can permanently delete the dungeon route.
     */
    public function forceDelete(User $user, DungeonRoute $dungeonroute): bool
    {
        return $user->hasRole(Role::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can schedule a publish for a dungeon route.
     * Requires the route to be in a team, and the user to be a moderator or higher in that team.
     */
    public function schedulePublish(User $user, DungeonRoute $dungeonRoute): Response
    {
        if ($dungeonRoute->team_id === null) {
            return $this->deny(__('policy.schedule_publish_route_not_in_team'));
        }

        if ($dungeonRoute->isOwnedByUser($user) || $user->hasRole(Role::ROLE_ADMIN)) {
            return $this->allow();
        }

        if ($dungeonRoute->team->isUserModerator($user)) {
            return $this->allow();
        }

        return $this->deny();
    }
}
