<?php

namespace App\Models;

use App\User;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
 * @method static Builder isModerator(int $userId)
 *
 * @mixin Eloquent
 */
class TeamUser extends Model
{
    /**
     *
     * @param Builder $query
     * @param int $userId
     * @return Builder
     */
    function scopeIsModerator(Builder $query, int $userId)
    {
        return $query->where('user_id', $userId)->whereIn('role', ['admin', 'moderator']);
    }

    /**
     * @return BelongsTo
     */
    function team()
    {
        return $this->belongsTo('App\Models\Team');
    }

    /**
     * @return BelongsTo
     */
    function user()
    {
        return $this->belongsTo('App\User');
    }
}
