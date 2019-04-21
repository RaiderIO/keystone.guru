<?php

namespace App\Models;

use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int
 * @property $team_id int
 * @property $user_id int
 * @property $role string
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Team $team
 * @property User $user
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
