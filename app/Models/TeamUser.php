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
    const ROLE_MEMBER       = 'member';
    const ROLE_COLLABORATOR = 'collaborator';
    const ROLE_MODERATOR    = 'moderator';
    const ROLE_ADMIN        = 'admin';

    const ALL_ROLES = [
        self::ROLE_MEMBER       => 1,
        self::ROLE_COLLABORATOR => 2,
        self::ROLE_MODERATOR    => 3,
        self::ROLE_ADMIN        => 4,
    ];

    protected $fillable = ['team_id', 'user_id', 'role'];

    /**
     *
     * @param Builder $query
     * @param int $userId
     * @return Builder
     */
    function scopeIsModerator(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId)->whereIn('role', [self::ROLE_ADMIN, self::ROLE_MODERATOR]);
    }

    /**
     * @return BelongsTo
     */
    function team(): BelongsTo
    {
        return $this->belongsTo('App\Models\Team');
    }

    /**
     * @return BelongsTo
     */
    function user(): BelongsTo
    {
        return $this->belongsTo('App\User');
    }
}
