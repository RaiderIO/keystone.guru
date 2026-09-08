<?php

namespace App\Policies;

use App\Models\Laratrust\Role;
use App\Models\MapIcon;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class MapIconPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create a map icon that is not attached to a dungeon route -
     * i.e. one that is part of the mapping itself and visible to everyone.
     */
    public function createGlobal(?User $user): Response
    {
        return $user !== null && $user->hasRole(Role::ROLE_ADMIN) ?
            $this->allow() :
            $this->deny(__('policy.create_global_map_icon_admin_only'));
    }

    /**
     * Determine whether the user can update an existing map icon while editing a dungeon route.
     * A mapping icon (no dungeon route, no team) belongs to the mapping - this endpoint is only
     * ever reached with an incoming dungeon route, so allowing it here would let anyone (even an
     * admin) reassign a global mapping icon to a personal route, which was never possible before
     * this policy existed. That transition is denied unconditionally, not just non-admin-gated.
     *
     * A team icon carries no dungeon route to gate on, so the team it belongs to is what decides:
     * the same collaborator requirement that assignToTeam() applies when the icon is put on a team
     * in the first place. Admins are allowed here for the same reason they are in delete().
     */
    public function update(?User $user, MapIcon $mapIcon): Response
    {
        if ($mapIcon->dungeon_route_id !== null) {
            return $this->allow();
        }

        if ($mapIcon->team_id !== null) {
            return $this->assignToTeam($user, $mapIcon, $mapIcon->team) || ($user !== null && $user->hasRole(Role::ROLE_ADMIN)) ?
                $this->allow() :
                $this->deny(__('policy.update_team_map_icon_collaborator_only'));
        }

        return $this->deny(__('policy.update_map_icon_admin_only'));
    }

    /**
     * Determine whether the user can delete the map icon. Anything not attached to a dungeon route
     * is admin-only.
     *
     * Note this is deliberately stricter than update(): a team icon is updatable by a collaborator
     * but only deletable by an admin. That asymmetry is pre-existing behaviour, preserved verbatim
     * here rather than silently changed.
     *
     * Callers must still authorize 'edit' on the icon's dungeon route separately - "edit" and not
     * "delete", so team members cannot delete each other's map comments.
     */
    public function delete(?User $user, MapIcon $mapIcon): Response
    {
        if ($mapIcon->dungeon_route_id !== null) {
            return $this->allow();
        }

        return $user !== null && $user->hasRole(Role::ROLE_ADMIN) ?
            $this->allow() :
            $this->deny(__('policy.delete_map_icon_admin_only'));
    }

    /**
     * Determine whether the user can attach a map icon to the given team, which makes it visible
     * to every member of that team.
     */
    public function assignToTeam(?User $user, MapIcon $mapIcon, ?Team $team): bool
    {
        return $team !== null && $user !== null && $team->isUserCollaborator($user);
    }
}
