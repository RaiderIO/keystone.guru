<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @property $id int
 * @property $icon_file_id int
 * @property $name string
 * @property $description string
 *
 * @property Collection $members
 * @property Collection $dungeonroutes
 *
 * @mixin \Eloquent
 */
class Team extends IconFileModel
{
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
}
