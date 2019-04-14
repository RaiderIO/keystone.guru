<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @property $id int
 * @property $team_id int
 * @property $user_id int
 * @property $role string
 *
 * @property Collection $team
 * @property Collection $user
 *
 * @mixin \Eloquent
 */
class TeamUser extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function team()
    {
        return $this->belongsTo('App\Models\Team');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function user()
    {
        return $this->belongsTo('App\User');
    }
}
