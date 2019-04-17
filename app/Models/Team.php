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
    /**
     * @var array List of current roles for a user in a team.
     */
    private static $_roles = ['member' => 1, 'collaborator' => 2, 'moderator' => 3, 'admin' => 4];

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
     * @param $user User
     * @return array
     */
    public function getAssignableRoles($user)
    {
        $userRole = $this->getUserRole($user);
        $result = [];

        if ($userRole !== false) {
            // Count down from all roles that exist, starting by the role the user currently has
            $userRoleKey = self::$_roles[$userRole];
            for ($i = $userRoleKey; $i > 0; $i--) {
                // array_search to find key by value
                $result[] = array_search($i, self::$_roles);
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

        // Only if it's a valid role
        if (isset(self::$_roles[$role])) {
            $userRole = $this->getUserRole($user);
            $targetUserRole = $this->getUserRole($targetUser);

            if ($userRole !== false && $targetUserRole !== false) {
                $userRoleKey = self::$_roles[$userRole];
                $targetUserRoleKey = self::$_roles[$targetUserRole];
                $targetRoleKey = self::$_roles[$role];

                // User has a bigger role, and then only up to where the current user is (no promotions past their own
                // rank) the person, and only users who are currently a moderator or admin may change roles
                $result = $userRoleKey > $targetUserRoleKey && $userRoleKey >= $targetRoleKey && $userRoleKey >= 3;
            }
        }

        return $result;
    }

    public function changeRole($user, $role)
    {
        $teamUser = $this->teamusers()->where('user_id', $user->id)->get()->first();
        // Only when user is part of the team, and when the role is a valid one.
        if ($teamUser !== null && isset(self::$_roles[$role])) {
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
     * Checks if a user is a member of this team or not.
     * @param $user User
     * @return bool
     */
    public function isUserMember($user)
    {
        return $user !== null ? $this->members()->where('user_id', $user->id)->count() === 1 : false;
    }

    /**
     * Adds a member to this team.
     *
     * @param $user User
     * @param $role string
     */
    public function addMember($user, $role)
    {
        $teamUser = new TeamUser();
        $teamUser->team_id = $this->id;
        $teamUser->user_id = $user->id;
        $teamUser->role = $role;
        $teamUser->save();
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
}
