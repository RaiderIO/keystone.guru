<?php

namespace App\Models;

use App\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * @property $id int
 * @property $icon_file_id int
 * @property $name string
 * @property $description string
 * @property $invite_code string
 *
 * @property Collection $members
 * @property Collection $dungeonroutes
 *
 * @mixin \Eloquent
 */
class Team extends IconFileModel
{

    public function getRouteKeyName()
    {
        return 'name';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function teamusers()
    {
        return $this->hasMany('App\Models\TeamUser');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    function members()
    {
        return $this->belongsToMany('App\User', 'team_users');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function dungeonroutes()
    {
        return $this->hasMany('App\Models\DungeonRoute');
    }

    /**
     * Checks if a user can add/remove a route to this team or not.
     * @param $user User
     * @return boolean
     */
    public function canAddRemoveRoute(User $user)
    {
        $userRole = $this->getUserRole($user);
        $roles = config('keystoneguru.team_roles');
        // Moderator or higher
        return $roles[$userRole] >= 3;
    }

    /**
     * Adds a route to this Team.
     *
     * @param DungeonRoute $dungeonRoute The route to add.
     * @return boolean True if successful, false if not (already assigned, for example).
     */
    public function addRoute(DungeonRoute $dungeonRoute)
    {
        $result = false;
        // Not set
        if ($dungeonRoute->team_id <= 0) {
            $dungeonRoute->team_id = $this->id;
            $dungeonRoute->save();
            $result = true;
        }

        return $result;
    }

    /**
     * Removes a route from this Team.
     *
     * @param DungeonRoute $dungeonRoute The route to remove.
     * @return boolean True if successful, false if not (already removed, for example).
     */
    public function removeRoute(DungeonRoute $dungeonRoute)
    {
        $result = false;
        // Set already
        if ($dungeonRoute->team_id > 0) {
            $dungeonRoute->team_id = -1;
            $dungeonRoute->save();
            $result = true;
        }

        return $result;
    }

    /**
     * Get the role of a user in this team, or false if the user does not exist in this team.
     * @param $user User
     * @return string|boolean
     */
    public function getUserRole($user)
    {
        /** @var TeamUser $teamUser */
        $teamUser = $this->teamusers()->where('user_id', $user->id)->get()->first();
        return $teamUser === null ? false : $teamUser->role;
    }

    /**
     * Get the roles that a user may assign to other users in this team.
     * @param $user User The user attempting to change roles.
     * @param $targetUser User The user that is targeted for a role change.
     * @return array
     */
    public function getAssignableRoles($user, $targetUser)
    {
        $userRole = $this->getUserRole($user);
        $targetUserRole = $this->getUserRole($targetUser);
        $result = [];

        // If both users have a valid role (should always be the case)
        if ($userRole !== false && $targetUserRole !== false) {
            $roles = config('keystoneguru.team_roles');
            $userRoleKey = $roles[$userRole];
            $targetUserRoleKey = $roles[$targetUserRole];
            // For now, admins cannot be demoted to anything else
            if ($targetUserRoleKey !== 4) {
                // If the current user is a moderator or admin, and (if user is admin or the current user outranks the other user)
                if ($userRoleKey >= 3 && ($userRoleKey === 4 || $userRoleKey > $targetUserRoleKey)) {

                    // Count down from all roles that exist, starting by the role the user currently has
                    for ($i = $userRoleKey; $i > 0; $i--) {
                        // array_search to find key by value
                        $result[] = array_search($i, $roles);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Checks if a specific user may change the role of another user into a specific other role.
     *
     * @TODO Should this go to a Policy?
     *
     * @param $user User
     * @param $targetUser User
     * @param $role string
     * @return boolean
     */
    public function canChangeRole($user, $targetUser, $role)
    {
        $result = false;
        $roles = config('keystoneguru.team_roles');

        // Only if it's a valid role
        if (isset($roles[$role])) {
            $userRole = $this->getUserRole($user);
            $targetUserRole = $this->getUserRole($targetUser);

            if ($userRole !== false && $targetUserRole !== false) {
                $userRoleKey = $roles[$userRole];
                $targetUserRoleKey = $roles[$targetUserRole];
                $targetRoleKey = $roles[$role];

                // User has a bigger role, and then only up to where the current user is (no promotions past their own
                // rank) the person, and only users who are currently a moderator or admin may change roles
                $result = $userRoleKey > $targetUserRoleKey && $userRoleKey >= $targetRoleKey && $userRoleKey >= 3;
            }
        }

        return $result;
    }

    /**
     * Changes the role of a user in this team.
     * @param $user User The user of which the role should be changed.
     * @param $role string The new role of the user.
     */
    public function changeRole($user, $role)
    {
        $teamUser = $this->teamusers()->where('user_id', $user->id)->get()->first();
        $roles = config('keystoneguru.team_roles');
        // Only when user is part of the team, and when the role is a valid one.
        if ($teamUser !== null && isset($roles[$role])) {
            // Update the role with the new one
            $teamUser->role = $role;
            $teamUser->save();
        }
    }

    /**
     * @return bool Checks if the current user is a member of this team.
     */
    public function isCurrentUserMember()
    {
        return $this->isUserMember(Auth::user());
    }

    /**
     * Checks if the user is a collaborator or higher.
     * @param $user  User
     * @return bool True if the user is, false if not.
     */
    public function isUserCollaborator($user)
    {
        $userRole = $this->getUserRole($user);
        return $userRole !== false && $userRole !== 'member';
    }

    /**
     * Gets if a user is a moderator and may perform moderation actions.
     *
     * @param $user User
     * @return bool
     */
    public function isUserModerator($user)
    {
        $userRole = $this->getUserRole($user);
        return $userRole === 'moderator' || $userRole === 'admin';
    }

    /**
     * Checks if a user is a member of this team or not.
     * @param $user User
     * @return bool
     */
    public function isUserMember($user)
    {
        return $user !== null ? $this->members()->where('user_id', $user->id)->count() === 1 : false;
    }

    /**
     * Checks if a user can remove another user from the team.
     * @param User $user
     * @param User $targetUser The user that's to be removed.
     * @return boolean
     */
    public function canRemoveMember(User $user, User $targetUser)
    {
        $userRole = $this->getUserRole($user);
        $targetUserRole = $this->getUserRole($targetUser);
        $roles = config('keystoneguru.team_roles');
        // Moderator or higher
        $userRoleKey = $roles[$userRole];
        $targetUserRoleKey = $roles[$targetUserRole];
        // Be admin, or moderator that's removing normal users
        return $userRoleKey === 4 || ($userRoleKey === 3 && $userRoleKey > $targetUserRoleKey);
    }

    /**
     * Removes a member from this Team.
     *
     * @param User $member The user to remove.
     * @return boolean True if successful, false if not (already removed, for example).
     */
    public function removeMember(User $member)
    {
        $result = false;
        // Only if the user could be found..
        if ($this->isUserMember($member)) {
            /** @var TeamUser $teamUser */
            $teamUser = TeamUser::where('team_id', $this->id)->where('user_id', $member->id)->firstOrFail();
            try {
                $result = $teamUser->delete();
            } catch (\Exception $exception) {
                // YOLO
            }
        }

        return $result;
    }

    /**
     * Adds a member to this team.
     *
     * @param $user User
     * @param $role string
     */
    public function addMember($user, $role)
    {
        // Prevent duplicate member listings
        if (!$this->isUserMember($user)) {
            $teamUser = new TeamUser();
            $teamUser->team_id = $this->id;
            $teamUser->user_id = $user->id;
            $teamUser->role = $role;
            $teamUser->save();
        }
    }

    /**
     * @return string Generates a random invite code.
     */
    public static function generateRandomInviteCode()
    {
        do {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $newKey = '';
            for ($i = 0; $i < 12; $i++) {
                $newKey .= $characters[rand(0, $charactersLength - 1)];
            }
        } while (Team::all()->where('public_key', $newKey)->count() > 0);

        return $newKey;
    }

    public static function boot()
    {
        parent::boot();

        // Delete route properly if it gets deleted
        static::deleting(function ($item) {
            /** @var $item Team */

            // Delete icons
            if ($item->iconfile !== null) {
                $item->iconfile->delete();
            }

            // Remove all users associated with this team
            TeamUser::where('team_id', $item->id)->delete();
            // Unassign all routes from this team
            DungeonRoute::where('team_id', $item->id)->update(['team_id' => -1]);
        });
    }
}
