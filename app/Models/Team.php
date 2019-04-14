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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    function dungeonroutes()
    {
        return $this->belongsToMany('App\Models\DungeonRoute', 'team_dungeon_routes');
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
        return Auth::check() ? $this->members()->where('user_id', Auth::id())->count() === 1 : false;
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
